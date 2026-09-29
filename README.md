# Publikai

Plataforma **interna da Jaguartec Tecnologia** para automatização e gerenciamento de
operações de conteúdo voltadas a **marketing de afiliados**.

> Sprint 0.1 — fundação técnica: autenticação interna, shell administrativo
> responsivo, dashboard inicial e documentação base. Nenhum módulo de
> produtos, IA, redes sociais ou afiliados implementado ainda.

O Publikai **não é um SaaS comercial**: sem assinaturas, billing, planos,
checkout, multi-tenancy comercial, white-label, onboarding de clientes ou marketplace.

## Stack

- PHP 8.5.6 · Laravel 13.33.0 · Composer 2.10.0
- Blade + Tailwind CSS v4 + JavaScript (Vite)
- Node 24.16.0 · npm 11.16.0
- MariaDB 10.4.32 local (`publikai_db`)
- FFmpeg/FFprobe 9.0.2 (instalado e validado no ambiente Windows; ainda não usado pelo app)

## Requisitos

- PHP 8.3+, Composer, Node 24+, MariaDB/MySQL, Git.

## Instalação local (Windows 11)

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Preencha no `.env`: `DB_*` (MariaDB), `MAIL_*` e as variáveis abaixo.

```sh
php artisan migrate --force
npm install
npm run build   # ou: npm run dev
php artisan serve
```

## Banco

- MariaDB local `publikai_db` (`DB_CONNECTION=mysql`, `127.0.0.1:3306`).
- Migrations oficiais do Laravel preservadas + `2026_09_29_000001_add_role_to_users_table`
  (`users.role`: `admin`/`operator`; primeiro usuário `admin`, demais `operator`).
- Nunca edite migration aplicada; crie uma nova.

## Cadastro interno

```ini
REGISTRATION_ENABLED=true
REGISTRATION_CODE=
```

- `false` desliga o cadastro (GET e POST de `/register` retornam 403).
- Código preenchido passa a ser exigido e é comparado **somente no servidor**
  (`hash_equals`); nunca vai ao frontend nem a logs.

## Comandos de desenvolvimento

```sh
php artisan serve
npm run dev
php artisan migrate
php artisan test
npm run build
./vendor/bin/pint
```

## Testes

```sh
php artisan test
```

Cobertura Sprint 0.1 (`tests/Feature/Auth/`): login válido/inválido, dashboard
com/sem autenticação, cadastro habilitado/desabilitado, código válido/inválido,
papel do 1º/2º usuário e logout. Testes usam SQLite em memória (`phpunit.xml`).

## Documentação

- `docs/00-VISION.md` — visão e escopo
- `docs/01-ARCHITECTURE.md` — stack e padrão Jaguartec
- `docs/02-DATABASE.md` — banco e migrations
- `docs/03-AI-ARCHITECTURE.md` — IA (planejado; Gemini previsto, sem integração)
- `docs/04-CONTENT-PIPELINE.md` — pipeline (planejado)
- `docs/05-SOCIAL-INTEGRATIONS.md` — redes sociais (planejado)
- `docs/06-AFFILIATE-TRACKING.md` — afiliados (planejado)
- `docs/07-SECURITY.md` — segurança aplicada
- `docs/08-DEPLOY.md` — ambientes
- `docs/09-ROADMAP.md` — roadmap
- `docs/10-DECISIONS.md` — decisões
- `docs/sprints/` — uma nota por Sprint finalizada

## Segurança

Segredos só no `.env` (nunca versionado). `.env.example` contém apenas nomes e
valores fictícios. Sem tokens, senhas ou API keys no código ou nos logs.
