# 05 — Integrações sociais

**Estado:** parcial (contas manuais implementadas na Sprint 2; OAuth/APIs pendentes) · **Atualizado em:** 2026-09-29

## Implementado (Sprint 2)

- Gestão de múltiplas contas (`social_accounts`, cadastro **manual**): nome,
  plataforma (Instagram/TikTok/YouTube), username, URL, idioma/mercado, nicho,
  público, tom, estilo, CTA padrão, frequência, status, observações.
- Account DNA cadastrado e organizado para o futuro Content Engine (sem
  interpretação por IA nesta Sprint).
- Autorização `SocialAccountPolicy` (mesma regra de archived de produtos).
- Interface: listagem `ui.table`, formulário em blocos, detalhe com seções
  Conta + Account DNA, sidebar Distribuição/Contas ativa.

## Ainda planejado (não implementado)

- OAuth por rede, armazenamento seguro de tokens, publicação e agendamento
  **somente via APIs oficiais**.
- Adaptação de formato por rede social; fila nativa para publicações.
- Calendário e Publicações seguem como “Em breve” no menu.

## Proibições vigentes

Sem `access_token`/`refresh_token` no banco, sem Meta/TikTok/Google conectados,
sem scraping, sem upload de avatar.
