# Sprint 5.6.5 — Content Pipeline Orchestrator (video-first)

## 1. Pré-check e baseline

- Branch `main` em `c469a79`, working tree limpa.
- Baseline: 329/329, 1279 assertions. Pint passed. Build OK.
- Docs relidas (00/03/04/07/08/09/10/11 + sprints 5.6.0–5.6.4).

## 2. Escopo (só orquestra, nada novo)

- Nenhum provider/modelo/TTS/composição/caption/Cost Guard novo.
- Pipeline padrão: image → video → audio → merge (`video_master`).
- Composição segue manual/opcional, fora do pipeline.

## 3. Nova entidade

- `content_productions`: `content_script_id`, `status`
  (pending/processing/success/failed), `current_step`
  (preparing/image/video/audio/finalizing/completed), `force_new`,
  `image/video/audio/final_media_asset_id` nullable, `error_code` sem
  detalhe técnico, `created_by`, `started_at/completed_at`.
- Filhas ganham `content_production_id` nullable + `nullOnDelete`
  (image/video/audio/merge). Fluxos manuais seguem sozinhos.

## 4. Serviço e Job

- `ContentProductionService::start()` (lock + 1 ativa por Script;
  retry reabre a falhada do ponto da falha) e `advance()` (só passos
  síncronos; reuse-first via `ProductionFlowService`; cria request
  filha + dispatch e retorna).
- `RunContentProductionJob` (tries=1, 60s): lock, ignora terminal,
  chama `advance()`, nunca espera filho, nunca faz IA.
- Continuação: trait `RelaysContentProduction` no fim do `handle()` e
  no `failed()` dos 4 Jobs filhos → `childFinished()` → fail (só se o
  filho MAIS NOVO de cada tipo falhou) ou `advance()`. Sem polling.
- `markStep` ANTES do dispatch (fila sync em testes executa inline;
  marcar depois sobrescrevia fail/success — lição registrada).
- `VideoMotionPromptBuilder`: determinístico (Script/Blueprint/Persona/
  Avatar/objetivo), sem IA extra. Reusa `VisualPromptBuilder`,
  `NarrationTextBuilder`, voice default (sem migration de voz).

## 5. Reuse-first e retry

- Final válido do Script + `force_new=false` → success sem gerar nada.
- Imagem: `recommended_image_id`; vídeo: latest (composed > ai, nunca
  merged como input); áudio: latest; `force_new=true` reutiliza imagem,
  gera vídeo novo, reutiliza narração, novo merge (documentado).
- Falha filha → production failed, sem retry automático caro.
- Retry (mesma produção via `start()`): preserva snapshots, cria só o
  que falta. Double-submit: ativa existente + flash "já está sendo".
- `Http::fake` acumula (first-match-wins): testes de retry usam UMA
  closure com flag `&$failVideo`, nunca dois fakes.

## 6. UI (`/content/{id}`)

- Review vira `POST content.produce` ("Produzir vídeo").
- Produção ativa: card "Produção do vídeo" com etapas amigáveis
  (Preparando/Preparando visual/Criando vídeo/Criando narração/
  Finalizando vídeo), sem provider/model/request IDs; "Produzindo
  vídeo… Atualize a página" (manual, sem polling/websocket).
- Failed: "Não foi possível concluir o vídeo." + "Tentar novamente".
- Success: checklist + player final (via partial) + "Criar nova versão"
  (`force_new=1`). Sem percentual falso.

## 7. Testes (`ContentProductionTest`, 12)

- Start/double-submit, happy path fake completo (1 image/video/audio/
  merge → merged), reuse image/video+audio→merge, final-existe,
  force_new, image-fail (nada além), video-fail + retry (1 img/2 vid),
  merge-fail + retry direto (2 merges), POST/auth/elegibilidade,
  motion builder, detail processing/success. Sem rede/FFmpeg real
  (Http fake, inspectors/merger fakes, Storage::fake).
- Total: 341/341, 1343 assertions. Pint passed. Build OK.

## 8. QA (sem custo real)

- Fakes + fixtures por estado (approved vazio, processing por etapa,
  success, failed). Detail validado em desktop/mobile via suite
  (sem Google/FFmpeg/worker real). Worker parado → pending exibe
  "Produzindo vídeo… Atualize a página" (não é erro).
- Pergunta de compreensão: usuário vê só etapas amigáveis + player.

## 9. Deploy

- `queue:work` obrigatório (4 Jobs filhos + orchestrator, driver
  database). Sem Supervisor/systemd nesta Sprint.

## 10. Riscos

- `ProductionFlowService` como fonte de reuse acopla orchestrator à
  jornada; `latestFailedChild` considera só o filho mais novo por tipo;
  retry de failed reabre a MESMA produção (não cria nova); sync em
  testes esconde timing real da fila (mark-before-dispatch cobre).

## 11. Git status

- Branch `main`, alterações não commitadas. Sem tag/push.

## 12. Sugestão de commit

```
feat(ai-5.6.5): implement content pipeline orchestrator

- ContentProduction state machine with reuse-first video pipeline
- RunContentProductionJob plus child relay continuation, no blocking
- Friendly produce/retry UI on content detail
```

## 13. Auditoria de robustez assincrona (pos-implementacao, sem commit)
- Risco confirmado: catch + report sem rethrow deixava production presa em pending/processing; relay tambem engolia excecao. Correcao: RunContentProductionJob::handle catch -> failUnexpected (internal_error, completed_at, sem mensagem crua) + report, sem rethrow (tries=1, sem retry caro); failed() idempotente cobre hard timeout.
- Relay: ordem request-persiste-terminal primeiro, depois childFinished; catch faz failUnexpected best-effort + report (nunca presa). failed() das 4 child Jobs persiste failed + relay (dupla notificacao absorvida: advance e idempotente).
- Duplicate relay seguro (1 request/etapa); reentrada com child pending nao duplica (pending e re-despachado de forma segura, processing so aguarda); stale Job em terminal e no-op.
- Falha entre steps: snapshot preservado, retry reusa; request criada mas dispatch falhou: proxima reentrada re-despacha SOMENTE pending (processing nunca duplicado, sem custo duplo). Prioridade: snapshot > child da production > reuse do Script > nova request.
- Corrida de double POST: lock transacional reduz a janela mas sem constraint parcial nao ha garantia total sob concorrencia real (documentado; database queue e o alvo, sem comportamento Redis-specific).
- Bug latente corrigido: GenerateImageJob catch usava classe inexistente ImageGenerationStatus (agora ImageGenerationRequestStatus).
- Testes novos: ContentProductionRobustnessTest (10). Continuacao provada com services frescos por etapa (estado so no banco). Nenhum retry automatico pago; UI intocada.


## 14. Microajuste: status real em Meus conteudos
- ContentStatusResolver (regra unica): video com producao relevante -> pending/processing=Processando, success=Pronto, failed=Falhou; sem producao ou imagem -> contrato atual do Script; approved vazio -> Pronto para produzir.
- Relevante = ativa mais recente, senao ultima (por id). Nova versao em processamento mostra Processando; retry reaberta volta a Processando.
- Index com eager productions (1 query/pagina, sem N+1); show usa resolver no badge (detail/lista sem contradicao). Sem migration, sem state machine alterada.


## 15. Fechamento (revisao humana aprovada)
- Humano validou A-E no navegador: ORCHESTRATOR INTUITIVO. Microajustes finais: StoreContentRequest com mensagens PT-BR; dashboard sem conceito roteiro-centrado; sidebar sem Sprint 0.2 (Publikai - Jaguartec).
- ContentStatusResolver auditado: pending/processing=Processando, success=Pronto, failed=Falhou, approved vazio=Pronto para produzir, imagem preservada.
- Fixtures [QA 5.6.5] (scripts 25-29 + producoes + merge + asset + arquivo qa-565-final.mp4 + scripts temp) 100% removidos; ai_generations (14) e assets historicos preservados; jobs/failed_jobs 0.
- Risco residual: lock transacional reduz corrida de producao dupla, sem constraint parcial sob concorrencia extrema (nao resolver agora).
- Roadmap: 5.6.5 concluida; 5.6.6 Captions; 5.6.7 Cost Guard. 5.6.6 NAO iniciada.

