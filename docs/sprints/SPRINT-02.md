# Sprint 2 — Contas sociais + Account DNA

**Período:** 2026-09-29 · **Status:** concluída, aguardando revisão (sem commit/tag/push).

## 1. Pré-check do banco

- `git status` limpo; `migrate:status` inicial **falhou** (MariaDB parado) →
  parada conforme a ordem; responsável ligou o banco via XAMPP.
- Reconexão confirmada com `create_products_table` e
  `create_affiliate_links_table` aplicadas. Só então a implementação começou.

## 2. Auditoria inicial

- Docs relidos (00/01/02/04/05/09/10/11/SPRINT-01). Padrões reutilizados:
  status string + enum, `updateStatus` na policy, FormRequests base,
  `ui.table`, sidebar `activeLinks`, status travado p/ operator.
- Produtos e AffiliateLinks preservados integralmente (só o refactor de
  `config`, abaixo, os toca — sem mudança de comportamento).

## 3. Modelagem implementada

`SocialAccount` (cadastro manual, sem OAuth/tokens/relacionamentos futuros).
DNA: language, market, niche, audience, tone, content_style, default_cta,
posting_frequency. Sem soft delete; sem tabelas languages/markets.

## 4. Migration

- `2026_09_29_000004_create_social_accounts_table` (`social_accounts`,
  `unique(platform, username)`, índices `platform` e `status`).
- Nenhuma migration aplicada foi editada. Aplicada no MariaDB local.
- Decisão de unicidade: mesmo username em redes diferentes permitido;
  duplicado na mesma plataforma rejeitado (collation case-insensitive).

## 5. Enums

- `SocialPlatform` (`instagram/tiktok/youtube` + `label()`/`badgeVariant()`).
- `SocialAccountStatus` (`active/paused/archived` + `label()`/`badgeVariant()`).
- String no banco, sem ENUM nativo; sem abstração genérica prematura.

## 6. Model

- `App\Models\SocialAccount` (casts, `isArchived()`, factory). Sem
  relacionamentos com Persona/Avatar/Campaign/Publication/ReferenceProfile.

## 7. Policies

- `SocialAccountPolicy` (ver/criar/editar ambos; `updateStatus` idêntica à de
  produtos: entrar/sair de `archived` só admin). Descoberta por convenção.

## 8. Form Requests

- `SocialAccountRequest` (base) + `Store/UpdateSocialAccountRequest`:
  `platform` enum, `username` obrigatório ≤100 com `Rule::unique` escopado por
  plataforma (+ `ignore` no update), `profile_url` url, listas via
  `config('locale-options.*')`, `prepareForValidation` normaliza username
  (trim + remove @) antes da checagem de unicidade.

## 9. Controller

- `SocialAccountController` fino (index/create/store/show/edit/update,
  paginação 15, `updateStatus` no store/update como em produtos).
- Sem Service (CRUD simples; única regra especial vive na Policy).

## 10. Rotas

`social-accounts.index/create/store/show/edit/update` sob `auth`.
Sem delete físico.

## 11. Telas

- `index`: page-header ("Contas", "Perfis sociais gerenciados pelo Publikai.",
  "Nova conta"), `ui.table` (Conta/Plataforma/Mercado/Idioma/Nicho/Status/
  Atualização/Ações), badges de plataforma, `@username`, empty-state
  ("Nenhuma conta cadastrada").
- `create/edit` + `_form` em blocos (Identificação/Mercado/Account DNA/Controle),
  grids desktop, 1 coluna mobile, status travado p/ operator em conta arquivada.
- `show`: page-header com breadcrumb, card Conta (`dl`), card Account DNA em
  destaque moderado, sem contadores de módulos inexistentes.
- Sidebar: Distribuição/Contas ativa (`aria-current`); Calendário e Publicações
  seguem "Em breve". Dashboard **inalterado** (5º card geraria poluição visual).

## 12. Alterações no Design System

Nenhum componente novo (tabela e demais `ui.*` reutilizados). Sem mudança em
`docs/11-DESIGN-SYSTEM.md`.

## 13. Account DNA

Campos e exemplo real validados no QA (Beauty Finds US, §18). Apenas
cadastrado e organizado; sem interpretação por IA.

## 14. Autorização

Matriz igual a produtos: ver/criar/editar ambos; entrar/sair de `archived`
só admin (403 + testes); operator `active↔paused` livre.

## 15. Testes

`php artisan test`: **44/44 passaram** (139 assertions; 33 preservados + 11 novos
em `tests/Feature/SocialAccounts/`): guest, index, create, normalização de @,
validações (platform/profile_url/language/market), update, repetição entre
plataformas, duplicidade rejeitada, matriz archived completa, admin
arquiva/reativa.

## 16. Pint

`./vendor/bin/pint --test`: **passed** (após corrigir linha dupla em
`routes/web.php`, resíduo de edição da Sprint 1).

## 17. Build

`npm run build`: **OK** (Vite 8.3.1).

## 18. QA manual

Servidor local + sessão autenticada real: `/social-accounts` (200, empty-state),
create (200), cadastro Beauty Finds US via POST → show 200 (Conta + Account DNA,
badges Instagram/Ativa/US). Sem browser desktop (limitação conhecida);
desktop/mobile por classes responsivas (tabela em wrapper, truncate em URLs,
drawer preservado).

## 19. Documentação

`02-DATABASE.md` (tabela + `locale-options`), `04-CONTENT-PIPELINE.md` (DNA
orientará o engine), `05-SOCIAL-INTEGRATIONS.md` (parcial), `09-ROADMAP.md`
(Sprint 2 concluída), `10-DECISIONS.md` (decisão 20), este arquivo.
`11-DESIGN-SYSTEM.md` inalterado (nada novo criado).

## 19b. Correção pós-revisão (pré-commit)

Bug visual: username exibia literal `{{ $account->username }}` em index/show
(causa: `@{{ }}` é escape do Blade, não `@` + valor). Corrigido para
`{{ '@'.$account->username }}` (escaping seguro, sem `{!!}`). Teste
`test_username_e_renderizado_com_valor_real` garante valor real e ausência da
string literal em ambas as telas.

## 20. Pendências

- QA visual humano das telas de contas.
- OAuth/tokens/publicação/métricas: sprints futuras.
- `Refactor locale-options`: testes de produtos revalidam (28→44 verdes);
  reverter é trivial se a revisão discordar.

## 21. Riscos

- `unique(platform, username)` case-insensitive (collation): "Foo" e "foo"
  colidem na mesma plataforma — comportamento desejado, documentado.
- Username com @ é normalizado silenciosamente (helper no formulário avisa).
- MariaDB via XAMPP precisa estar ligado p/ dev local (sem serviço Windows).

## 22. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 23. Sugestão de commit

```
feat(social-2): implement social accounts with account DNA

- SocialAccount manual CRUD (Instagram/TikTok/YouTube), DNA fields
- SocialPlatform/SocialAccountStatus enums, SocialAccountPolicy (archived rule)
- locale-options config refactor, sidebar Contas, ui.table reuse
- Docs: 02/04/05/09/10 updated, SPRINT-02
```
