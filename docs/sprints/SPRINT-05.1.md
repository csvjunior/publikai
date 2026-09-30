# Sprint 5.1 — AI Reference Analysis

**Período:** 2026-09-29 · **Status:** concluída, aguardando revisão (sem commit/tag/push).

## 1. Pré-check e baseline

- Tree limpa; baseline: 92/92 testes, Pint passed, build OK.

## 2. Migration/model/enum

- `000011_create_reference_analyses_table` (`reference_analyses`: profile FK
  cascade, status, provider/model, summary text, 10 colunas JSON, confidence
  decimal(3,2), error_code/message, started/completed_at; índice (profile, status)).
- `ReferenceAnalysis` (casts enum/array/float/datetime, `isSuccess()`),
  `ReferenceAnalysisStatus` (pending/processing/success/failed + labels/badges),
  `ReferenceAnalysisFactory`. `ReferenceProfile::referenceAnalyses()` (latest).

## 3. Schema e prompt

- `App\AI\Schemas\ReferenceAnalysisSchema`: `schema()` (12 campos, confidence
  0–1, sem score de viralidade) + `instructions()` versionáveis (só dados
  cadastrados, sem URLs/scraping, sem métricas inventadas, sem atributos
  sensíveis, sem Persona/Avatar).

## 4. Service

- `ReferenceAnalysisService::analyze()`: elegibilidade (perfil ativo + ≥1
  conteúdo ativo; pendente/processing bloqueia duplicata) → pending →
  processing → `AiService::generate('reference_analysis', ...)` → valida
  resposta (summary, listas de strings, confidence 0..1) → success ou failed
  sanitizado. Exceções `AnalysisInProgressException` /
  `InsufficientAnalysisContextException` traduzidas em flash no controller.
- `AiService::generate()` genérico extraído (testConnection delega; sem
  duplicar logging; `ai_generations` com operation `reference_analysis`).

## 5/6. Fluxos, duplicata, rotas/controller

- Success persiste padrões + confidence; failed persiste error sanitizado;
  página nunca quebra; retry = nova análise (histórico imutável).
- `POST /references/{profile}/analyses` (`references.analyses.store`,
  `throttle:5,1`); autoriza `view` (ambos os papéis, sem RBAC novo).

## 7/8/9. UI, histórico, confidence

- Seção Inteligência no detalhe: botão "Analisar com IA" (desabilitado +
  helper sem config), empty state, resultado (listas + cards, sem JSON cru),
  failed amigável (hint p/ service_unavailable), histórico newest-first,
  `latestSuccessful` em destaque (senão latest). Confiança em % + helper
  ("consistência dos padrões", sem promessa de viralizar).

## 10. Provider disabled

- Botão desabilitado + "Configure a IA em Sistema → IA..."; sem 500.

## 11. Factory e testes

- Factory sem seeders. **104/104 passaram** (350 assertions; 92 preservados +
  12 novos, `Http::fake`, sem rede). Incidentes: `@if` dentro de tag de
  componente e card não fechado (Blade), ordem elegibilidade/duplicata,
  factories com status aleatório, fakes que acumulam (sequence).

## 12/13. Pint e build

- Pint **passed**; build **OK** (Vite 8.3.1).

## 14. QA manual

- Servidor local + sessão autenticada: index/create/show 200, seção
  Inteligência (botão, empty state) verificada no markup. Fluxos
  success/failed/edge cases cobertos pelos 12 testes automatizados
  (`Http::fake`, sem rede). Sem browser desktop (limitação conhecida);
  responsividade por classes (cards empilhados, tabelas em wrapper).
- **Incidente real encontrado e corrigido no QA:** `GET /references/{id}` → 500
  (`reference_analyses` inexistente no MariaDB — migration `000011` criada mas
  nunca aplicada localmente; testes usam sqlite e não acusaram). Corrigido com
  `migrate --force` (setup, sem mudança de código); show 200 reverificado.
  Lição: rodar `migrate:status` no QA de toda Sprint com migration nova.
- POST de análise real **não disparado** no QA (provider habilitado com chave
  real; sem gastar quota sem autorização).

## 14b. Microcorreção (feedback + timezone, pré-commit)

- Diagnóstico da falha do QA: `error_code rate_limited` legítimo do provider
  (google/`gemini-3.8-flash`); causa do verde incorreto: flash fixo de sucesso
  no controller. Corrigido: flash por status final + mensagens por error_code.
- Timezone: banco segue UTC; `APP_DISPLAY_TIMEZONE=America/Sao_Paulo` +
  `Carbon::display()`; QA local mostra 16:36/16:40/16:52 (UTC 19:52−3).
  Testes: flash success/3× failed + conversão determinística 22:52→19:52.

## 14c. Microcorreção bloqueadora (timeout síncrono, pré-commit)

- Causa do FatalError: `GOOGLE_AI_TIMEOUT=30` × até 2 tentativas = ~60s de HTTP
  contra `max_execution_time=30` do php.ini — PHP matava o processo antes do
  timeout controlável (análise ficava presa em processing).
- Correção: timeout 10s/tentativa + connect 5s (config, sem hardcode), retry
  mantido só p/ 429/5xx → pior caso ~20s + overhead < 30s. Sem `set_time_limit`,
  sem Job (futuro quando justificar).
- Análise id 4 (QA, presa pelo fatal) marcada manualmente failed/timeout para
  destravar o perfil; scripts temporários removidos.
- Testes: timeout controlado (failed + log + redirect + flash, sem
  pending/processing, show 200), featured preservado, budget via config.

## 14d. QA real final (pré-fechamento)

- Única análise real via fluxo normal: `failed`/`timeout` (`gemini-3.8-flash`)
  em **~10.9s**, redirect normal, flash amigável, 0 pendentes, success anterior
  preservada, timezone correto, sem segredo em logs, sem FatalError novo.
- Dados QA (`[QA] Visual Review` + dependências) removidos após a revisão;
  dados reais do responsável preservados (incluindo `ai_generations`).
- Lições: (A) feedback depende do status final; (B) UTC no banco +
  `APP_DISPLAY_TIMEZONE` na apresentação; (C) orçamento 10s+5s < 30s PHP;
  (D) fila desnecessária nesta Sprint — retry manual pelo histórico basta.

## 15. Documentação

`02-DATABASE.md` (tabela + relação), `03-AI-ARCHITECTURE.md` (primeira análise
real), `09-ROADMAP.md` (Sprint 5.1), `10-DECISIONS.md` (decisão 26), este arquivo.
`11-DESIGN-SYSTEM.md` inalterado (só reuso).

## 16. Pendências

- QA visual humano (seção Inteligência, resultado, histórico).
- Teste real de structured output (backlog 5.0) validaria o caminho completo.
- Fila/Jobs se volume justificar; propostas de Persona/Avatar (futuro).

## 17. Riscos

- Execução síncrona + timeout 30s: OK p/ volume interno atual; migrar p/ fila
  se análises demorarem.
- Sem delete físico: histórico cresce; housekeeping via archived do perfil.

## 18. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 19. Sugestão de commit

```
feat(ai-5.1): implement AI reference analysis

- ReferenceAnalysis history + ReferenceAnalysisService (stored data only)
- Versioned schema/instructions, sync execution, sanitized failure
- Intelligence section on reference detail, latestSuccessful featured
- Docs: 02/03/09/10 updated, SPRINT-05.1
```
