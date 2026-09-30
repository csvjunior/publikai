# Sprint 5.3 — Content Blueprints

**Período:** 2026-09-30 · **Status:** concluída, **revisão visual humana aprovada**, commit + push realizados no fechamento.

## 1. Pré-check e baseline

- Tree limpa; baseline: 128/128 testes, Pint passed, build OK.

## 2. Migration/model/enums

- `000013_create_content_blueprints_table` (`content_blueprints`, slug único,
  `source_type`, FKs opcionais, índices `status`/`content_type`). Aplicada.
- `ContentBlueprint` (casts, `sourceProfile()`, `sourceAnalysis()`,
  `isArchived()`, `isManual()`), `ContentBlueprintStatus`,
  `ContentBlueprintSourceType` (manual/ai_assisted), factory sem seeders.

## 3. Source type

- `manual` automático no `ContentBlueprintService::create()` (ignora input do
  usuário); `ai_assisted` só como valor reservado p/ futuro.

## 4. Config content types

- Taxonomia reutilizada de `config/references.php` (mesmo conceito: categoria
  estrutural); sem duplicação. Decisão 28.

## 5. Service/slug e policy/requests/controller/rotas

- `ContentBlueprintService` (slug único, espelho do ProductService; extensão
  p/ AI-assisted). `ContentBlueprintPolicy` (`updateStatus`, mesma matriz).
- Requests base + Store/Update (listas controladas, duration `min:1`, sem
  `source_type` do usuário). Controller fino; rotas `blueprints.*` sob `auth`.

## 6/7/8. Index, form, detail e sidebar

- Index: page-header, `ui.table` (7 colunas), empty-state dedicada.
- Form em 5 blocos (grids desktop, 1 coluna mobile; "Arquivado" só admin).
- Show: Estratégia/Estilo/Contexto/Origem (manual ou links de origem).
- Sidebar Inteligência/Blueprints ativa.

## 9. Factory e testes

- **139/139 passaram** (486 assertions; 128 preservados + 11 novos, sem rede).
  Incidentes: título de card ("Estrutura"→"Estratégia") e origem manual no
  teste de FKs (ajustado p/ `ai_assisted`).

## 10/11. Pint e build

- Pint **passed**; build **OK** (Vite 8.3.1).

## 12. QA manual

- Servidor local + sessão autenticada: index/create/show/edit 200, cadastro do
  exemplo QA (Curiosity UGC Beauty US) via POST, badges e seções no markup.
- Sem browser desktop (limitação conhecida); responsividade por classes
  (tabela em wrapper, `dl` 1–2 colunas, truncate).

## 13. Documentação

`02-DATABASE.md` (tabela + modelagem), `03-AI-ARCHITECTURE.md` (blueprints
manuais), `04-CONTENT-PIPELINE.md` (estado parcial), `09-ROADMAP.md`
(Sprint 5.3), `10-DECISIONS.md` (decisão 28), este arquivo.
`11-DESIGN-SYSTEM.md` inalterado (só reuso).

## 14. Pendências

- QA visual humano (listagem, form, detalhe) — **aprovado**: index, formulário
  e detail claros; origem manual sem blocos vazios; estrutura reutilizável,
  não roteiro.
- AI-assisted Blueprint (ReferenceAnalysis + Persona + Avatar + Product →
  proposta → revisão); vínculo Product no Script Studio/Campaign.

## 14b. Regras de conteúdo e taxonomia (fechamento)

- Blueprint armazena **padrões abstratos** (ex. correto: "Curiosity → problem →
  reveal → demonstration → soft CTA"); nunca roteiro literal, fala/caption
  copiada ou conteúdo integral de creator.
- Taxonomia `content_types` compartilhada com References (mesmo conceito);
  **pendência**: separar a config se divergirem semanticamente no futuro.
- `source_type` manual automático e não adulterável; `ai_assisted` reservado.

## 15. Riscos

- Taxonomia compartilhada: se references precisar de tipo exclusivo, separar
  (hoje idênticas por desenho).
- Sem delete físico: housekeeping via archived.

## 16. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 17. Sugestão de commit

```
feat(intel-5.3): implement content blueprints

- ContentBlueprint manual CRUD (slug, source_type, shared taxonomy)
- Statuses, policy with archived rule, ui.table, sidebar Blueprints
- Docs: 02/03/04/09/10 updated, SPRINT-05.3
```
