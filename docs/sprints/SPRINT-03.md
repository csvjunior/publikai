# Sprint 3 — Personas e avatares

**Período:** 2026-09-29 · **Status:** concluída, **revisão visual humana aprovada**, commit + push realizados no fechamento.

## 1. Pré-check

- `git status` limpo; `migrate:status` OK com `create_products_table`,
  `create_affiliate_links_table` e `create_social_accounts_table` aplicadas.

## 2. Auditoria

- Docs relidos (00/01/02/03/04/09/10/11/SPRINT-02). Padrões espelhados:
  status string + enum, `updateStatus` na policy, Requests base, CRUD fino,
  `ui.table`, status travado p/ operator, sidebar `activeLinks`.
- Products e SocialAccounts preservados (SocialAccount ganha FKs + selects).

## 3. Migrations

- `2026_09_29_000005_create_personas_table`, `..._000006_create_avatars_table`,
  `..._000007_add_default_identity_to_social_accounts_table`
  (FKs nullable `nullOnDelete`). Nenhuma aplicada foi editada; aplicadas no MariaDB.

## 4. Modelagem Persona

`personas`: name, language/market (códigos), audience, personality, tone,
communication_style, vocabulary, expressions(text), content_preferences(text),
avoidances(text), default_cta_style, status, notes. Sem soft delete.

## 5. Modelagem Avatar

`avatars`: name, apparent_age(string), gender_presentation,
ethnicity_description (manual, sem inferência), hair, eyes, skin,
body_description, default_clothing, visual_style, preferred_scenarios,
voice_description, language/market, reference_notes(text, instrução futura),
status, notes. Sem upload, sem assets, sem storage novo.

## 6. Enums

`PersonaStatus` e `AvatarStatus` (`active/paused/archived` + `label()`/
`badgeVariant()`); string no banco, sem abstração genérica.

## 7. Models

`Persona`, `Avatar` (casts, `isArchived()`, factories). `SocialAccount` ganha
`default_persona_id`/`default_avatar_id` (fillable) + `defaultPersona()`/
`defaultAvatar()` (belongsTo). Sem inversos (não usados em UI/testes).

## 8. Policies

`PersonaPolicy`, `AvatarPolicy` (ver/criar/editar ambos; `updateStatus`
idêntico: entrar/sair de `archived` só admin). Convenção, sem registro.

## 9. Requests

`PersonaRequest` base + Store/Update; `AvatarRequest` base + Store/Update
(listas em `locale-options`, textos sem restrição excessiva).
`SocialAccountRequest` aceita `default_persona_id`/`default_avatar_id`
(`nullable|exists`, IDs inexistentes rejeitados).

## 10. Controllers

`PersonaController`, `AvatarController` finos (index/create/store/show/edit/
update, paginação 15, `updateStatus` como nos demais). Sem Service (CRUD
simples). `SocialAccountController`: `identityOptions()` (só ativos/pausados)
em create/edit + eager load dos defaults no show.

## 11. Rotas

`personas.*` e `avatars.*` (index/create/store/show/edit/update) sob `auth`.
Sem destroy.

## 12. Telas Persona

`index` (page-header, `ui.table` 7 colunas, empty-state "Nenhuma persona
cadastrada"), `_form` em 5 blocos (textareas p/ expressões/preferências/
evitar), `create/edit`, `show` com **Communication DNA** (`dl` organizado).

## 13. Telas Avatar

`index` (`ui.table` 8 colunas, empty-state), `_form` em 7 blocos (sem upload),
`create/edit`, `show` com **Visual DNA**.

## 14. Alteração SocialAccount

Form ganha bloco **Identidade de conteúdo** (selects c/ "Nenhuma/Nenhum",
só ativos/pausados); show exibe Persona/Avatar padrão (links) ou
"Não definida"/"Não definido". Index sem novas colunas (sem poluição).

## 15. Sidebar

Creative Studio: **Personas, Avatares** ativos no topo do grupo (implementados
e fundacionais); Ideias/Roteiros/Imagens/Vídeos seguem "Em breve". Sem
redesenho; demais grupos intactos.

## 16. Componentes Design System

Nenhum criado/alterado (só reuso). `11-DESIGN-SYSTEM.md` inalterado.

## 17. Factories

`PersonaFactory`, `AvatarFactory` (dados seguros). Sem seeders.
`SocialAccountFactory` inalterada (FKs opcionais via testes).

## 18. Testes

`php artisan test`: **62/62 passaram** (218 assertions; 45 preservados + 17
novos): `PersonaTest` (8: guest, index, create, validações, update, matriz
archived completa), `AvatarTest` (6: guest, index, create, validações, update,
matriz equivalente), +3 em `SocialAccountTest` (ids válidos, inexistentes
rejeitados, sem identidade + show apresenta).

## 19. Pint

`./vendor/bin/pint --test`: **passed** (após auto-fix de ordenação de imports
em `routes/web.php` — só estilo, sem mudança semântica).

## 20. Build

`npm run build`: **OK** (Vite 8.3.1).

## 21. QA manual

Servidor local + sessão autenticada: personas/avatars index (200, empty-states),
cadastro Emma US Beauty + Emma via POST → shows 200 (Communication/Visual DNA).
Sem browser desktop (limitação conhecida); responsividade por classes
(grids `sm:`, tabelas em wrapper, `dl` 1–2 colunas).

**Revisão visual humana (responsável técnico, 2026-09-29): APROVADA.**
Personas, avatares, DNAs, selects na conta e detalhe validados.

## 22. Documentação

`02-DATABASE.md` (tabelas Sprint 3), `03-AI-ARCHITECTURE.md` (continua sem IA),
`04-CONTENT-PIPELINE.md` (DNAs cadastrados), `09-ROADMAP.md` (Sprint 3),
`10-DECISIONS.md` (decisão 21), este arquivo. `11-DESIGN-SYSTEM.md` inalterado.

## 23. Pendências

- QA visual humano (personas, avatares, selects na conta).
- ReferenceProfile, Blueprint, Campaign, geração, assets, OAuth: futuros.

## 24. Riscos

- `apparent_age` como string aceita "27" ou "25–30" (flexível p/ personagem;
  sem validação numérica — decisão consciente).
- `ethnicity_description` livre exige disciplina editorial (ferramenta interna;
  sem automação que a preencha).
- FKs `nullOnDelete` sem delete físico hoje: comportamento futuro já coerente.

## 25. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 26. Sugestão de commit

```
feat(creative-3): implement personas and avatars

- Persona (Communication DNA) + Avatar (Visual DNA) manual CRUD
- PersonaStatus/AvatarStatus enums, policies with archived rule
- SocialAccount default_persona/avatar with selects and detail display
- Sidebar Creative Studio: Personas/Avatares active
- Docs: 02/03/04/09/10 updated, SPRINT-03
```
