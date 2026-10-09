# Sprint 5.6.4 — Simplificação / Content Creator (refactor)

> Reescreve a nota anterior (jornada no Script), preservando o trabalho:
> `ProductionFlowService`, parcial Produção, `ProductionFlowTest` e QA
> scripts 19–24 foram REUTILIZADOS; nada descartado.

## 1. Pré-check e baseline

- Branch `main`, working tree limpa em `2a8739a` + trabalho 5.6.4 anterior
  (só jornada, sem commit). Baseline: 319/319, 1206 assertions.
- Docs relidas (00/03/04/09/10/11-DESIGN-SYSTEM + sprints 5.6.0–5.6.3).

## 2. Auditoria do trabalho anterior

- REUTILIZAR: `ProductionFlowService` (+ recommended_action/estados),
  partial Produção, `ProductionFlowTest`, fixtures 19–24, terminologia.
- AJUSTAR: card "Produção" (direção) → "Direção de produção" (nome livre
  p/ jornada); testes de sidebar legados (menu mudou, rotas vivas).
- DESCARTAR: nada.

## 3. Nova navegação

- Sidebar: Dashboard; CONTEÚDO (Criar conteúdo, Meus conteúdos);
  IDENTIDADE (Personas, Avatares); CATÁLOGO (Produtos); INTELIGÊNCIA
  (Referências); DISTRIBUIÇÃO (Contas, Publicações em breve); SISTEMA
  (IA, Custos/Configurações em breve). Técnicos fora (rotas vivas).
- Mobile drawer compartilha o parcial (mesma simplificação).

## 4. Criar conteúdo e serviços

- `GET /content/create` (cards Vídeo/Imagem) → formulário simples
  (Produto/Persona/Avatar + objetivo + orientação + avançado colapsado).
- `ContentCreatorService`: resolve entidades (não-arquivadas),
  Blueprint default (mais antigo ativo; 422 orientando cadastro se vazio),
  gera roteiro via IA existente, grava tipo/objetivo/notes. Sem nova IA.
- Review: hook/body/cta + "Produzir vídeo" (→ fluxo atual) + "Editar
  roteiro" + helper de custo; sem produção automática (5.6.5).
- "Meus conteúdos" agrega roteiros (tipo/status amigáveis, sem IDs);
  detalhe reusa jornada + detalhes recolhíveis. Sem orchestrator.

## 5. Tipo sem inferência frágil

- `content_scripts.content_type` nullable + enum (migration mínima,
  justificada: intenção só existe na criação); fallback por inferência
  p/ linhas antigas. Produto/Persona/Avatar seguem obrigatórios (FKs
  não-nulas; sem `Sem X` para não tocar 6 sprints de domínio).

## 6. Dashboard e Personas/Avatares

- Dashboard: ações "Criar vídeo/imagem" + card Conteúdos linkado.
- Personas ("como se comunica") e Avatares ("quem aparece") mantidos;
  voz default global (sem migration; próximo passo documentado).

## 7. Testes

- `ContentCreatorTest`: 9 testes (sidebar nova/legado, create, store ×3,
  lista, review, detalhe, serviço + legado). Sidebar legados atualizados.
- Total: 328/328, 1266 assertions. Pint passed. Build OK.

## 8. QA

- Fixtures [QA] reutilizados (19–24) + cenários de criação; desktop +
  mobile; sem Google/FFmpeg. Teste de compreensão no fechamento.

## 8b. Conclusão final (fechamento)

- Auditoria: sidebar/menu simplificados, dashboard e fluxos validados ao
  vivo; `content_type` nullable com inferência; rotas legadas intactas.
- Fixtures 19–24 + dependências removidos (12 assets, 7 requests, 5
  pivots, 12 arquivos); asset 1, request 1 e ai_generations históricos
  preservados; jobs 0/0; sem órfãos.

## 9. Pendências

- QA visual humano. Orchestrator 5.6.5, Captions 5.6.6, Cost Guard 5.6.7,
  Distribution depois.

## 10. Riscos

- Heurística de tipo/status em linhas antigas; telas permitem qualquer
  combinação válida; checkpoint depende de revisão humana real.

## 11. Git status

- Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 12. Sugestão de commit

```
feat(ai-5.6.4): implement content creator simplification

- Result-oriented nav with create flow and review checkpoint
- Content list/detail reusing guided production journey
- Docs: 00/04/09/10 updated, SPRINT-05.6.4 rewritten
```
