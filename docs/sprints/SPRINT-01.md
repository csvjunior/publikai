# Sprint 1 — Produtos afiliados

**Período:** 2026-09-29 · **Status:** concluída, aguardando revisão (sem commit/tag/push).

## 1. Auditoria inicial

- Docs relidos: 00-VISION, 01-ARCHITECTURE, 02-DATABASE, 06-AFFILIATE-TRACKING,
  09-ROADMAP, 10-DECISIONS, 11-DESIGN-SYSTEM, SPRINT-00.2.
- Git: branch `main` limpa no commit `b74d011`.
- Models: só `User`; sem policies (só Gate `access-admin`); rotas só auth +
  dashboard; 10 componentes `ui/`; testes 13/13; storage padrão (sem uploads
  nesta Sprint — nenhuma config de storage necessária).

## 2. Objetivo e modelagem

Módulo de Produtos Afiliados (primeiro caso real: produto US em inglês para
Instagram — campanhas/conteúdo seguem futuros).
`Product hasMany AffiliateLink` / `AffiliateLink belongsTo Product`.
Sem soft delete; sem tabelas languages/markets; sem conversão cambial.

## 3. Migrations criadas

- `2026_09_29_000002_create_products_table` (`products`, slug único, índice `status`).
- `2026_09_29_000003_create_affiliate_links_table` (`affiliate_links`, FK
  `product_id` com `cascadeOnDelete`, índice `(product_id, is_primary)`).
- Nenhuma migration aplicada foi editada. Aplicadas no MariaDB local.

## 4. Models/enums criados

- `App\Enums\ProductStatus` (`active`/`paused`/`archived`, com `label()` e
  `badgeVariant()`); status como string no banco (sem ENUM nativo).
- `App\Models\Product` (casts `decimal:2` + enum, `affiliateLinks()`,
  `primaryLink()`, `isArchived()`), `App\Models\AffiliateLink`.
- `config/products.php`: idiomas (`en-US`, `pt-BR`), mercados (`US`, `BR`),
  moedas (`USD`, `BRL`), tipos de comissão (`percent`, `fixed`). Banco guarda códigos.

## 5. Services criados

- `ProductService`: slug único a partir do nome (regenera ao renomear).
- `AffiliateLinkService`: unicidade do link principal em transação
  (create/update). Sem `ProductService` vazio: só existe pela regra do slug.

## 6. Form Requests criados

- `ProductRequest` (base abstrata) + `StoreProductRequest` + `UpdateProductRequest`:
  listas via `Rule::in(config)`, `product_url` url, preços `>= 0`, status enum.
- `AffiliateLinkRequest`: label/url obrigatórios, market controlado, `is_primary` boolean.

## 7. Controllers/Policies

- `ProductController` (index/create/store/show/edit/update, fino; arquivamento
  autorizado via `archive` quando destino = `archived`).
- `AffiliateLinkController` (store/update/destroy; `is_primary` via
  `$request->boolean()`; `scopeBindings` garante posse do link).
- `ProductPolicy` (ver/criar/editar p/ ambos; `archive` só admin),
  `AffiliateLinkPolicy` (criar/editar ambos; `delete` só admin).
  Descoberta por convenção, sem registro manual.

## 8. Rotas

`products.index/create/store/show/edit/update` + `affiliate-links.store/update/destroy`
aninhadas com `scopeBindings`, todas sob `auth`. Sem delete físico de produto;
arquivamento = PUT com `status=archived` (via formulário de edição — sem rota dedicada).

## 9. Telas criadas (Design System)

- `products/index`: page-header + `ui.table` (Produto/Mercado/Idioma/Preço/Rede/
  Status/Atualização/Ações) + paginação + `empty-state` ("Nenhum produto cadastrado").
- `products/create|edit` + `_form` parcial em blocos (Informações/Mercado/Afiliado/
  Link original/Controle), grids desktop, 1 coluna mobile; opção "Arquivado"
  visível só p/ admin.
- `products/show`: page-header com breadcrumb, card de detalhes (`dl`), seção
  de links (badge "Principal" `ai`, URLs com truncate + "Abrir", edição inline
  em `<details>`, exclusão c/ confirmação só p/ admin, formulário de novo link).
- Dashboard: card "Produtos" com **contagem real** + link (grid agora 4 colunas no xl).

## 10. Componentes globais adicionados/alterados

- Novo: `x-ui.table` (+ `.pk-table` em `design-system.css`).
- Alterado: sidebar (item Produtos ativo com `aria-current`, sem badge "Em breve";
  demais itens intactos). Nenhum outro componente alterado.

## 11. Autorização

Matriz: visualizar/criar/editar produtos e links = admin + operator; arquivar
produto e excluir link = só admin (403 + testes). Arquivamento no store também
exige a transição. **Ajuste pré-commit:** qualquer transição para ou a partir
de `archived` exige admin (`ProductPolicy@updateStatus`); operators alternam
apenas entre `active`/`paused`. No-op (mesmo status) não é transição e continua
permitido. Interface: operator em produto arquivado vê status fixo + aviso,
sem opção de saída (demais campos seguem editáveis).

## 12. Testes e resultados

`php artisan test`: **28/28 passaram** (86 assertions; 13 preservados + 15 novos
em `tests/Feature/Products/`): lista auth/guest, create válido/inválido,
update, archive admin/operator, url inválida, link create/inválido, unicidade
do principal (create e update), delete operator(403)/admin, escopo entre produtos (404).

## 13. Pint / 14. Build

- `./vendor/bin/pint --test`: **passed**. · `npm run build`: **OK** (Vite 8.3.1).

## 15. QA manual

Servidor local: `/products` (200, empty-state), `/products/create` (200),
`/login` (200). Fluxos de cadastro/edição/links/arquivamento cobertos pelos
15 testes automatizados; sem browser desktop conectado (mesma limitação das
sprints anteriores). Desktop/mobile validados por classes responsivas
(`sm:`/`xl:`, drawer preservado, URLs com truncate, tabela em wrapper próprio).

## 16. Documentação atualizada

`02-DATABASE.md` (tabelas Sprint 1), `06-AFFILIATE-TRACKING.md` (parcial:
produtos + links), `09-ROADMAP.md` (Sprint 1 concluída), `10-DECISIONS.md`
(decisões 16–18), `11-DESIGN-SYSTEM.md` (`ui.table` implementado), este arquivo.

## 17. Pendências

- QA visual humano das telas de produtos (revisão).
- Error bag padrão nos formulários inline de links (erro de um form pode
  aparecer nos demais da mesma página; reavaliar se confundir).
- Cliques/conversões/tracking: Sprint de performance.

## 18. Riscos

- `details/summary` para edição inline: sem JS, acessível por teclado; se a
  UX não agradar na revisão, migrar para página dedicada (sem modal por estética).
- Preço `decimal(10,2)` limita a ~99M — suficiente p/ afiliados; ampliar via
  nova migration se um dia necessário.
- Sem dark mode / i18n de telas (fora do escopo).

## 19. Git status

Branch `main`, sem remote adicional, sem tag/push. Alterações não commitadas
(listadas na entrega).

## 20. Sugestão de mensagem de commit

```
feat(products-1): implement affiliate products module

- Product + AffiliateLink (migrations, models, services, policies, CRUD)
- Primary-link uniqueness in AffiliateLinkService, slug in ProductService
- Views with Design System (ui.table, sidebar Produtos, real count on dashboard)
- Docs: 02/06/09/10/11 updated, SPRINT-01
```
