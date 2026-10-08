# 08 — Deploy

**Estado:** desenvolvimento local · **Atualizado em:** 2026-09-29

## Ambiente local (confirmado)

- Windows 11, PHP 8.5.6, Composer 2.10.0, Laravel 13.33.0,
  Node 24.16.0, npm 11.16.0, MariaDB 10.4.32, Git 2.54.0,
  FFmpeg 9.0.2 / FFprobe 9.0.2 (instalado e validado no ambiente Windows;
  se o terminal atual não reconhecer os comandos, trata-se apenas de
  sessão com PATH desatualizado — sem reinstalação, sem bloqueio).

## Como rodar (local)

```sh
composer install
cp .env.example .env   # + preencher APP_KEY, DB_* e REGISTRATION_*
php artisan key:generate
php artisan migrate --force
npm install
npm run build          # ou: npm run dev
php artisan serve
```

## Produção (planejado, não executado)

- Alvo Linux; manter compatibilidade Windows (dev) ↔ Linux (prod).
- Servidor web + supervisor de filas quando houver jobs.
- `APP_DEBUG=false`, `MAIL_*` real, backups do banco.

## Filas de vídeo (Sprint 5.6.0)

- Local: `php artisan queue:work --tries=1 --timeout=600` (chamada síncrona
  longa dentro do Job; sem Redis/Horizon).
- Produção futura: worker dedicado via systemd/Supervisor + `FFPROBE_BINARY`
  no ambiente (só inspeção, sem composição). Sem config aqui.

## Pendências

- Definir hospedagem, pipeline de deploy e estratégia de backup.
- Inicializar repositório Git e definir fluxo de branches.
