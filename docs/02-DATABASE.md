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

## Modelagem atual

- `users`: `id, name, email (único), email_verified_at, password (hash), role, remember_token, timestamps`.
- `role` é `string` controlada no banco + `App\Enums\UserRole` (`admin`/`operator`) no PHP.
  - **Decisão:** string em vez de `ENUM` nativo do banco para compatibilidade
    MariaDB 10.4 ↔ MySQL 8 e evolução sem alteração destrutiva.
- Regra: o **primeiro usuário** criado via cadastro recebe `admin`; os demais, `operator`
  (ver `RegistrationService`). Sem sistema complexo de permissões nesta Sprint.
- Autorização futura: Gate `access-admin` registrado; Policies quando houver entidades de domínio.

## Regra permanente respeitada

Nenhuma migration já aplicada foi editada; a alteração de `users` foi feita
em migration nova com `up`/`down` reversíveis.
