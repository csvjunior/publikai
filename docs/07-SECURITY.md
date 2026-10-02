# 07 — Segurança

**Estado:** atual (Sprint 5.5.0 async) · **Atualizado em:** 2026-10-02

## Medidas aplicadas

- Cadastro, login, logout e recuperação de senha com validação **no servidor**
  (Form Requests + validação inline nos fluxos de senha).
- Senhas com **hash nativo** (`hashed` cast + `Hash::make`); nunca em texto puro.
- **CSRF mantido** em todos os formulários (`@csrf`).
- **Rate limiting no login** (`throttle:5,1` + bloqueio progressivo em
  `LoginRequest` com `RateLimiter`, 5 tentativas por e-mail+IP).
- Throttle também em cadastro e recuperação de senha (`throttle:5–10,1`).
- Cadastro interno controlável:
  - `REGISTRATION_ENABLED=false` bloqueia GET e POST de `/register` (403).
  - `REGISTRATION_CODE` comparado **somente no servidor** com `hash_equals`;
    nunca exposto ao frontend, nunca logado.
- Sessão regenerada após login/cadastro; invalidação + novo token CSRF no logout.
- Segredos no `.env` (não versionado). `.env.example` contém apenas
  nomes e valores fictícios/vazios.
- Recuperação de senha usa broker nativo (`password_reset_tokens`,
  expiração 60 min, throttle 60 s) com `MAIL_MAILER=log` em local.

## Verificado nesta Sprint

- Nenhuma chave, token ou senha no repositório (somente `REGISTRATION_CODE=`
  vazio no `.env.example`).
- Nenhum log de segredos no código implementado.

## IA e mídia (Sprints 5.x)

- Auth Key só no `.env`; nunca em código, logs, telas, testes ou banco.
  Mesma chave para texto e imagem (mesma API/família de endpoint).
- `ai_generations` sanitizado: sem prompts completos, sem bodies, sem base64.
- `MediaAsset`: sem base64 no banco; path relativo no Storage (sem absoluto);
  filename UUID (sem prompt, sem input de usuário); extensão pelo MIME
  detectado, não pelo usuário; `throttle` nas rotas de teste/generation.
- Prompt persiste **só** em `image_generation_requests` (funcional p/ o Job);
  nunca em logs nem em `ai_generations`. Fila `database` nativa, sem Redis.
- Uploads de usuário ainda não existem (quando existirem: validar MIME,
  extensão e tamanho).

## Pendências

- Avaliar verificação de e-mail, 2FA e política de senha além do mínimo (8 chars).
- Headers de segurança / CSP quando houver deploy.
