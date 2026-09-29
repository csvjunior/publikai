# Sprint 0.1 — Fundação técnica do Publikai

**Período:** 2026-09-29 · **Status:** concluída, aguardando revisão (sem push/tag).

## 1. Auditoria inicial (antes de alterar)

- `composer.json`: Laravel `^13.17` (instalado 13.33.0), sem starter kit de auth, sem Boost.
- `package.json`: Tailwind v4 + Vite + `laravel-vite-plugin`; `node_modules` ausente → instalado nesta Sprint.
- Estrutura Laravel 13 padrão; `routes/web.php` só com `/` → `welcome`.
- Migrations existentes: apenas as 3 oficiais (`users`, `cache`, `jobs`) — já aplicadas no MariaDB.
- Frontend: `resources/css/app.css` (Tailwind v4) + `welcome.blade.php`; sem build (`public/build` ausente).
- Nenhum starter kit ou autenticação instalado — confirmado.
- Git: **não é um repositório** (`fatal: not a git repository`).
- `.env` local já com `APP_NAME=Publikai` e MariaDB `publikai_db`; `.env.example` ainda `APP_NAME=Laravel` + `sqlite`.
- Nenhum conflito destrutivo encontrado; nada existente foi substituído automaticamente.

## 2. O que foi implementado

- **Identidade:** nome visível Publikai (`.env` + `.env.example` + layouts), visual simples/profissional Tailwind.
- **Autenticação interna** (nativa, sem pacotes): cadastro, login, logout, recuperação de senha.
  - `REGISTRATION_ENABLED` / `REGISTRATION_CODE` (`config/registration.php`), comparação servidor com `hash_equals`.
  - Rate limiting no login (throttle + `RateLimiter`, 5 tentativas) e nas rotas de cadastro/senha.
- **Usuários:** enum `UserRole` (`admin`/`operator`), migration de `users.role`, primeiro usuário `admin`, demais `operator`, Gate `access-admin` como expansão.
- **Shell admin responsivo:** sidebar desktop + drawer mobile, menu completo da operação com itens futuros marcados “Em breve” (somente Dashboard funcional, sem controllers vazios).
- **Dashboard:** saudação, identificação Publikai, aviso de fase inicial, 3 cards “Ainda sem dados” (sem métricas fabricadas).
- **Docs:** `docs/00–10` + este arquivo. Conteúdo reflete só o implementado; futuro marcado como planejado.
- **README** atualizado (objetivo, stack, instalação, banco, dev, testes, docs).
- **Testes:** `tests/Feature/Auth/{AuthenticationTest,RegistrationTest}.php` (11 casos novos) + ajuste do `ExampleTest` (`/` redireciona para `/login`).

## 3. Arquivos principais (criados/alterados)

- Criados: `app/Enums/UserRole.php`, `app/Services/RegistrationService.php`,
  `app/Http/Requests/Auth/{RegisterRequest,LoginRequest}.php`,
  `app/Http/Controllers/Auth/{RegisteredUserController,AuthenticatedSessionController,PasswordResetLinkController,NewPasswordController}.php`,
  `app/Http/Controllers/DashboardController.php`,
  `resources/views/layouts/{guest,app}.blade.php`, `layouts/partials/sidebar.blade.php`,
  `resources/views/auth/{login,register,forgot-password,reset-password}.blade.php`,
  `resources/views/dashboard.blade.php`, `config/registration.php`,
  `tests/Feature/Auth/*.php`, `docs/*.md`, `docs/sprints/SPRINT-00.md`.
- Alterados: `app/Models/User.php` (role), `app/Providers/AppServiceProvider.php` (Gate),
  `routes/web.php`, `database/factories/UserFactory.php`, `.env` (vars locais),
  `.env.example`, `tests/Feature/ExampleTest.php`, `README.md`.

## 4. Migrations

- Nova: `2026_09_29_000001_add_role_to_users_table` (`users.role string(20) default operator`).
- Oficiais preservadas, nenhuma editada. Aplicada com `migrate --force` no MariaDB local.

## 5. Variáveis de ambiente (novas)

- `REGISTRATION_ENABLED=true` (desliga cadastro com `false`).
- `REGISTRATION_CODE=` (vazio = sem exigência; preenchido = exigido e comparado no servidor).
- Ambas adicionadas ao `.env` local e ao `.env.example` (vazias/fictícias, sem segredos).

## 6. Testes e qualidade

- `php artisan test`: **13/13 passaram** (2 base + 11 novos de auth/cadastro).
- `npm run build`: OK (Vite 8.3.1, manifest gerado).
- Pint (`laravel/pint` já no projeto): a executar na verificação final.

## 7. Pendências

- Inicializar repo Git (aguardando revisão).
- i18n (`en` no config vs telas pt-BR), verificação de e-mail, 2FA — decisões futuras.

## 8. Riscos

- MariaDB 10.4 antigo: `php artisan db:show` falha (`performance_schema.session_status` ausente) — cosmético, migrations e app funcionam.
- Sem repo Git: sem histórico/backup versionado até o 1º commit.

## 9. Fechamento (2026-09-29)

- `.env.example` alinhado com MySQL/MariaDB (`DB_CONNECTION=mysql`,
  `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=publikai_db`,
  `DB_USERNAME=root`, sem senha real). `.env` local não alterado.
- FFmpeg 9.0.2 / FFprobe 9.0.2: instalado e validado no ambiente Windows
  (terminal atual com PATH desatualizado é apenas sessão antiga; sem
  reinstalação, sem bloqueio).
- Stack confirmada nos docs: PHP 8.5.6, Laravel 13.33.0, Composer 2.10.0,
  Node 24.16.0, npm 11.16.0, MariaDB 10.4.32, FFmpeg/FFprobe 9.0.2.
- Repositório Git local inicializado; primeiro commit criado (sem remote, sem tag).
- Validações reexecutadas: `php artisan test`, `./vendor/bin/pint --test`, `npm run build`.
