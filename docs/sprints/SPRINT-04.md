# Sprint 4 — Perfis e conteúdos de referência

**Período:** 2026-09-29 · **Status:** concluída, **revisão visual humana aprovada**, commit + push realizados no fechamento.

## 1. Pré-check

- `git status` limpo; `migrate:status` OK (todas as anteriores aplicadas).

## 2. Auditoria

- Docs relidos (00/01/02/03/04/05/09/10/11/SPRINT-03). Padrões espelhados:
  status string + enum, `updateStatus`, Requests base, CRUD fino, `ui.table`,
  status travado, sidebar `activeLinks`, nested + `scopeBindings` (AffiliateLink).
- Módulos anteriores preservados.

## 3/4/5. Migrations e modelagem

- `000008_create_reference_profiles_table` (`reference_profiles`: name,
  platform, username?, profile_url, language/market/niche?, reason(text)?,
  status, notes; índices platform/status).
- `000009_create_reference_contents_table` (`reference_contents`:
  profile FK cascade, url, title?, content_type?, observed_hook/structure/cta/
  style?, duration_seconds uint?, performance_notes(text)?, why_it_works(text)?,
  status, notes; índice (profile, status)).
- `ReferenceProfile hasMany ReferenceContent` / `belongsTo` (`profile()`).
  Relação nomeada `referenceContents()` — nome convencional **exigido** pelo
  `scopeBindings` (`{referenceContent}`), mesmo padrão de `affiliateLinks`.
- Sem vínculo com Product (contexto via language/market/niche); sem
  `ai_analysis`/embeddings/scores/prompts.

## 6. Enums/config

- `ReferenceProfileStatus`, `ReferenceContentStatus` (string, mesma matriz).
- `SocialPlatform` **reutilizado** (sem enum duplicado).
- `config/references.php`: 10 `content_types` com labels (sem tabela, sem ENUM).

## 7/8/9/10. Policies, requests, controllers, rotas

- `ReferenceProfilePolicy`, `ReferenceContentPolicy` (`updateStatus` idêntica).
- `ReferenceProfileRequest` base + Store/Update; `ReferenceContentRequest`
  (quick flow: só url + status bastam).
- Controllers finos, sem Service. Rotas `references.*` + `reference-contents.*`
  aninhadas com `scopeBindings` (cross-profile → 404).

## 11. Telas

- `index`: page-header, `ui.table` (Perfil/Plataforma/Mercado/Idioma/Nicho/
  Conteúdos[contagem real]/Status/Ações), empty-state dedicada.
- `_form` em 4 blocos (Identificação/Contexto/Análise manual/Controle).
- `show`: card Perfil (`dl`) + card Conteúdos (lista com badges, truncate +
  "Abrir", `<details>` "Ver análise e editar", form de novo conteúdo com
  status active/paused, status travado p/ operator em conteúdo arquivado).

## 12/13. Quick flow e detailed flow

- Rápido: perfil + URL (+ tipo opcional). Detalhado: hook/estrutura/CTA/estilo/
  duração/performance/why_it_works opcionais. Nada obrigatório além de URL.

## 14. Sidebar

Inteligência/Referências ativa (`aria-current`); Blueprints segue "Em breve".

## 15/16/17/18. Factories, testes, Pint, build

- Factories criadas, sem seeders.
- `php artisan test`: **74/74 passaram** (260 assertions; 62 preservados + 12
  novos): guest, index, create (mínimo e completo), validações (url/platform/
  duration), update, pertencimento/404, matriz archived (perfil e conteúdo).
- Incidente: `scopeBindings` quebrou 3 testes (`referenceContents()` vs
  `contents()`) — renomeado p/ convenção; suíte verde.
- Pint **passed**; build **OK** (Vite 8.3.1).

## 19. QA manual

Servidor local + sessão autenticada: index/create/show 200, cadastro Beauty
Creator US + 2 conteúdos via POST, badges e seção verificados no markup.
Sem browser desktop (limitação conhecida); responsividade por classes
(tabela em wrapper, truncate, `dl` 1–2 colunas, forms 1 coluna mobile).

**Revisão visual humana (responsável técnico, 2026-09-29): APROVADA.**
Cadastro simples, hierarquia adequada, seção de conteúdos clara, quick flow
coerente, empty states adequados, consistência com o Design System.

## 20. Documentação

`02-DATABASE.md` (tabelas + relação), `03-AI-ARCHITECTURE.md` (base p/ futura
análise, sem IA), `04-CONTENT-PIPELINE.md` (fluxo futuro documentado),
`09-ROADMAP.md` (Sprint 4), `10-DECISIONS.md` (decisão 23), este arquivo.
`11-DESIGN-SYSTEM.md` inalterado (só reuso).

## 21. Pendências

- QA visual humano (listagem, detalhe, `<details>` de análise).
- AI Analysis → Persona/Avatar Proposal → Human Review: próxima Sprint (só plano).
- Blueprints, campanhas, OAuth, publicação: futuros.

## 22. Riscos

- `duration_seconds` uint: vídeos >1193h estourariam (irrelevante p/ short-form).
- Sem delete físico: base cresce; housekeeping via archived (decisão consciente).
- `<details>` p/ análise+edição: se confuso na revisão, migrar p/ página dedicada.

## 23. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 24. Sugestão de commit

```
feat(intel-4): implement reference profiles and contents

- ReferenceProfile + ReferenceContent manual CRUD (patterns, no AI)
- Statuses, content_types config, policies with archived rule
- Detail with contents section (quick + detailed flow), sidebar Referências
- Docs: 02/03/04/09/10 updated, SPRINT-04
```
