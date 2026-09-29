# 01 — Arquitetura

**Estado:** atual (Sprint 0.1) · **Atualizado em:** 2026-09-29

## Stack atual

| Camada | Tecnologia |
|---|---|
| Runtime | PHP 8.5.6 |
| Framework | Laravel 13.33.0 |
| Dependências PHP | Composer 2.10.0 |
| Frontend | Blade + Tailwind CSS v4 + JavaScript |
| Build | Vite (laravel-vite-plugin) + Node 24.16.0 / npm 11.16.0 |
| Banco local | MariaDB 10.4.32 (`publikai_db`) |
| Vídeo (futuro) | FFmpeg 9.0.2 / FFprobe 9.0.2 — instalado e validado no ambiente Windows; ainda não usado pelo app |

## Padrão arquitetural Jaguartec (em vigor)

- Estrutura oficial do Laravel preservada.
- **Controllers** apenas coordenam requisições (`app/Http/Controllers`, `Auth/`).
- **Form Requests** fazem validação (`app/Http/Requests/Auth/`).
- **Services** concentram regras de negócio (`app/Services/RegistrationService.php`).
- **Models** representam entidades (`app/Models/User.php`).
- **Enums** para valores controlados (`app/Enums/UserRole.php`).
- **Gates** como ponto de expansão de autorização (`AppServiceProvider`: `access-admin`).
- **Jobs** apenas para operações realmente assíncronas (nenhum criado nesta Sprint).
- **Migrations** para toda alteração estrutural (nenhuma migration oficial editada).
- **Testes** para fluxos e regras críticas (`tests/Feature/Auth/`).

## O que existe nesta Sprint

- `config/registration.php` — controle de cadastro interno.
- Autenticação manual com recursos nativos do Laravel (session guard,
  password broker `users`, rate limiting no login). Nenhum starter kit
  (Breeze/Jetstream/Fortify) e nenhum pacote externo de auth instalado.
- Layouts Blade: `layouts/guest` (acesso) e `layouts/app` (shell admin
  responsivo com sidebar desktop + drawer mobile).
- Rotas em `routes/web.php` (guest/auth), CSRF mantido.

## Decisões

- Sem Vue, React, Redis, Docker ou microserviços (sem necessidade concreta).
- Sem Laravel Boost nesta Sprint (não instalado; sem justificativa de custo/benefício agora).
- Filas nativas do Laravel quando necessárias (config `QUEUE_CONNECTION=database`, sem uso ainda).

## Pendências

- i18n: locale do app segue `en`; telas escritas manualmente em pt-BR.
- Verificação de e-mail não ativada (avaliação futura).
