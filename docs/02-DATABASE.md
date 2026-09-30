# 02 — Banco de dados

**Estado:** atual (Sprint 0.1) · **Atualizado em:** 2026-09-29

## Conexão local

- Driver: `mysql` (MariaDB 10.4.32 em `127.0.0.1:3306`).
- Banco: `publikai_db`.
- `.env` local e `.env.example` alinhados com MySQL/MariaDB
  (`DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`,
  `DB_DATABASE=publikai_db`, `DB_USERNAME=root`, sem senha real).
  Alinhamento concluído no fechamento da Sprint 0.1.

## Migrations aplicadas

| Migration | Efeito |
|---|---|
| `0001_01_01_000000_create_users_table` (oficial) | `users`, `password_reset_tokens`, `sessions` — **preservada, sem edição** |
| `0001_01_01_000001_create_cache_table` (oficial) | `cache`, `cache_locks` — preservada |
| `0001_01_01_000002_create_jobs_table` (oficial) | `jobs`, `job_batches`, `failed_jobs` — preservada |
| `2026_09_29_000001_add_role_to_users_table` (**nova, Sprint 0.1**) | adiciona `users.role` (`string(20)`, default `operator`) |
| `2026_09_29_000002_create_products_table` (**nova, Sprint 1**) | `products` (ver modelagem abaixo) |
| `2026_09_29_000003_create_affiliate_links_table` (**nova, Sprint 1**) | `affiliate_links` com FK `product_id` + `cascadeOnDelete` |
| `2026_09_29_000004_create_social_accounts_table` (**nova, Sprint 2**) | `social_accounts` (ver modelagem abaixo) |
| `2026_09_29_000005_create_personas_table` (**nova, Sprint 3**) | `personas` (ver modelagem abaixo) |
| `2026_09_29_000006_create_avatars_table` (**nova, Sprint 3**) | `avatars` (ver modelagem abaixo) |
| `2026_09_29_000007_add_default_identity_to_social_accounts_table` (**nova, Sprint 3**) | FKs `default_persona_id`/`default_avatar_id` em `social_accounts` |
| `2026_09_29_000008_create_reference_profiles_table` (**nova, Sprint 4**) | `reference_profiles` (ver modelagem abaixo) |
| `2026_09_29_000009_create_reference_contents_table` (**nova, Sprint 4**) | `reference_contents` |
| `2026_09_29_000011_create_reference_analyses_table` (**nova, Sprint 5.1**) | `reference_analyses` (histórico imutável, padrões em JSON) |
| `2026_09_29_000012_create_identity_proposals_table` (**nova, Sprint 5.2**) | `identity_proposals` (propostas Persona/Avatar + rationale) |

## Modelagem atual

- `users`: `id, name, email (único), email_verified_at, password (hash), role, remember_token, timestamps`.
- `role` é `string` controlada no banco + `App\Enums\UserRole` (`admin`/`operator`) no PHP.
  - **Decisão:** string em vez de `ENUM` nativo do banco para compatibilidade
    MariaDB 10.4 ↔ MySQL 8 e evolução sem alteração destrutiva.
- Regra: o **primeiro usuário** criado via cadastro recebe `admin`; os demais, `operator`
  (ver `RegistrationService`). Sem sistema complexo de permissões nesta Sprint.
- Autorização futura: Gate `access-admin` registrado; Policies quando houver entidades de domínio.

## Regra permanente respeitada

Nenhuma migration já aplicada foi editada; as alterações foram feitas
em migrations novas com `up`/`down` reversíveis.

## Modelagem da Sprint 1 (produtos afiliados)

- `products`: `id, name, slug (único), description?, category?, product_url?,
  price decimal(10,2)?, currency(3)?, market(10)?, language(10)?,
  affiliate_network?, commission_type(20)?, commission_value decimal(10,2)?,
  status string(20) default `active`, notes?, timestamps` + índice em `status`.
- `status` é string controlada + `App\Enums\ProductStatus`
  (`active`/`paused`/`archived`) — sem `ENUM` nativo (mesmo motivo de `users.role`).
- `slug` gerado de `name` com sufixo de unicidade (`ProductService`).
- Sem soft delete; arquivamento = mudança de status (sem delete físico de produto).
- `affiliate_links`: `id, product_id (FK, cascade), label, url(2048),
  network?, market(10)?, is_primary bool default false, notes?, timestamps`
  + índice composto `(product_id, is_primary)`.
- `reference_profiles`: `id, name, platform(20), username(100)?,
  profile_url(2048), language(10)?, market(10)?, niche?, reason(text)?,
  status default `active`, notes?, timestamps` + índices `platform`, `status`.
- `reference_contents`: `id, reference_profile_id (FK, cascade), url(2048),
  title?, content_type(30)?, observed_hook?, observed_structure(text)?,
  observed_cta?, observed_style?, duration_seconds uint?, performance_notes(text)?,
  why_it_works(text)?, status default `active`, notes?, timestamps` + índice
  `(reference_profile_id, status)`.
- Relação `ReferenceProfile::referenceContents()` (nome convencional exigido
  pelo `scopeBindings` das rotas aninhadas — mesmo padrão de `affiliateLinks`).
- `ReferenceProfile::referenceAnalyses()` (latest first) + `ReferenceAnalysis`
  (histórico imutável de execuções com padrões em JSON sanitizado).
- `ReferenceProfile::identityProposals()` (latest) + `IdentityProposal`
  (persona_data/avatar_data/rationale em JSON; applied_* + applied_at no apply).
- Regra de unicidade lógica do link principal em `AffiliateLinkService`
  (transação; sem constraint parcial para manter compatibilidade MariaDB/MySQL).
- Banco guarda códigos (`US`, `en-US`, `USD`, `percent`); rótulos em
  `config/products.php` (listas específicas) e `config/locale-options.php`
  (idiomas/mercados compartilhados desde a Sprint 2).

## Modelagem da Sprint 2 (contas sociais)

- `social_accounts`: `id, name, platform(20), username, profile_url(2048)?,
  language(10)?, market(10)?, niche?, audience?, tone?, content_style?,
  default_cta?, posting_frequency?, status string(20) default `active`,
  notes?, timestamps`.
- `platform`/`status` como string + enums PHP (`SocialPlatform`,
  `SocialAccountStatus`) — sem `ENUM` nativo.
- Unicidade `unique(platform, username)`: mesmo nome permitido em redes
  diferentes, não duplicado na mesma plataforma.
- Sem soft delete; sem tokens OAuth; sem FKs para módulos inexistentes.

## Modelagem da Sprint 3 (personas e avatares)

- `personas`: `id, name, language(10)?, market(10)?, audience?, personality?,
  tone?, communication_style?, vocabulary?, expressions(text)?,
  content_preferences(text)?, avoidances(text)?, default_cta_style?,
  status string(20) default `active`, notes?, timestamps` + índice `status`.
- `avatars`: `id, name, apparent_age(20)?, gender_presentation?,
  ethnicity_description? (descrição visual manual, sem inferência),
  hair?, eyes?, skin?, body_description?, default_clothing?, visual_style?,
  preferred_scenarios?, voice_description?, language(10)?, market(10)?,
  reference_notes(text)? (instrução futura, sem upload), status default
  `active`, notes?, timestamps` + índice `status`.
- `status` como string + enums PHP (`PersonaStatus`, `AvatarStatus`).
- `social_accounts.default_persona_id` / `default_avatar_id` (FKs nullable,
  `nullOnDelete`): belongsTo reutilizável por várias contas, sem N:N.
  Arquivar persona/avatar não remove a referência (histórico); selects de
  troca listam só ativos/pausados.
