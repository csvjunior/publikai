# 06 — Rastreamento de afiliados

**Estado:** parcial (produtos + links implementados na Sprint 1) · **Atualizado em:** 2026-09-29

## Implementado (Sprint 1)

- Cadastro de produtos (`products`): nome, slug, descrição, categoria, URL,
  preço/moeda, mercado/idioma (códigos), rede, comissão, status, observações.
- Links de afiliado (`affiliate_links`, N por produto): rótulo, URL, rede,
  mercado, principal (único por produto, via `AffiliateLinkService`), observações.
- Arquivamento = mudança de status (só admin); sem delete físico de produto;
  exclusão de link só por admin.
- Autorização: `ProductPolicy` + `AffiliateLinkPolicy` (admin/operator).
- Interface: listagem com `ui.table`, formulário em blocos, detalhe com seção
  de links, sidebar "Produtos" ativa.
- Primeiro caso real previsto: produto afiliado para o mercado US, em inglês,
  promovido no Instagram (conteúdo/campanhas ainda não implementados).

## Ainda planejado (não implementado)

- Registro de conversões e associação com conteúdos/publicações.
- Tracking de cliques, shortener, campanhas.
- Menu segue com Campanhas e Conversões como “Em breve”.

## Pendências

- Modelar cliques e conversões na Sprint de performance.
