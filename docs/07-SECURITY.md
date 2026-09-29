# 07 — Segurança

**Estado:** atual (Sprint 0.1) · **Atualizado em:** 2026-09-29

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

## Pendências

- Avaliar verificação de e-mail, 2FA e política de senha além do mínimo (8 chars).
- Headers de segurança / CSP quando houver deploy.
