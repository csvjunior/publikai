# 11 — Design System, UX e Responsividade (contrato permanente)

**Estado:** contrato vigente · **Definido em:** 2026-09-29 · **Implementação inicial:** Sprint 0.2 (concluída, **revisão visual humana aprovada** em 2026-09-29)

> Este documento é o **contrato permanente** de experiência visual do Publikai.
> Vale para todas as Sprints futuras. Nenhuma nova tela deve criar estilos,
> espaçamentos, botões, formulários ou padrões responsivos arbitrários se já
> existir solução aqui. Alterações significativas neste contrato devem ser
> registradas em `docs/10-DECISIONS.md` e no documento da Sprint correspondente.

## Situação atual (honestidade de estado)

- **Contrato: definido** (este documento).
- **Implementação inicial: concluída na Sprint 0.2 e aprovada em revisão visual
  humana** (2026-09-29): tokens em `resources/css/design-system.css`;
  10 componentes em `resources/views/components/ui/`; shell, auth e dashboard
  migrados. Validados manualmente: cadastro, dashboard desktop e mobile,
  sidebar, drawer mobile, cards, tipografia, gutters, layout global, ausência
  de overflow horizontal e consistência geral.
- **Diferido (sem caso de uso limpo; sem mudança):** `modal`, `tabs`, `table`,
  componente dedicado `checkbox`/`radio`/`switch` — somente quando existir caso real.
- Novas telas devem usar tokens/componentes.

## 1. Filosofia visual

Ferramenta interna de conteúdo, IA, social media e afiliados. A identidade transmite:
tecnologia, criação, agilidade, clareza e organização.

- Visual moderno, claro e clean; superfícies discretas; hierarquia forte; baixa poluição.
- **Evitar** aparência pesada de ERP corporativo tradicional.
- Acento **violeta/índigo como identidade provisória** do produto.
- Não usar múltiplas cores fortes sem função semântica.
- Stack: Laravel Blade + Tailwind CSS v4 + JavaScript, mobile-first.
- **Não instalar** biblioteca de componentes UI externa sem autorização.

## 2. Design tokens

Arquivo canônico: `resources/css/design-system.css` (**implementado** na Sprint 0.2,
importado por `resources/css/app.css`, pipeline Tailwind v4 inalterado).
Não houve motivo técnico para outra estrutura. Tokens implementados:

| Grupo | Tokens |
|---|---|
| Superfície | `canvas`, `surface`, `surface-muted` |
| Texto | `text-primary`, `text-secondary`, `text-muted` |
| Borda | `border` |
| Ação | `primary`, `primary-hover` |
| Semântica | `success`, `warning`, `danger`, `info` |
| IA | `AI/accent` (quando necessário) |

Centralizar também: border radius (`--radius-card` → `rounded-card`), shadows
(`--shadow-card` → `shadow-card`), spacing, typography (classes `t-page-title`,
`t-section-title`, `t-card-title`, `t-body`, `t-small`, `t-muted`, `t-label`),
workspace gutters (`--gutter` responsivo + classe `.pk-workspace`), surface padding,
card padding, section gaps — **todos implementados**.

**Regra:** não espalhar valores arbitrários pelas páginas quando houver token correspondente.

## 3. Layout global

- **Sidebar desktop:** ~248px, persistente.
- **Área principal:** `max-width` global de ~1440px quando adequado.
- **Workspace gutters (pertencem ao shell global):**

| Breakpoint | Gutter |
|---|---|
| Desktop grande (≥1280px) | 32px |
| Notebook (1024–1279px) | 24px |
| Tablet (768–1023px) | 20px |
| Mobile (<768px) | 16px |

- Páginas individuais **não** recriam margens externas próprias sem justificativa.
- Diferenciar claramente: `workspace gutter` × `surface padding` × `section gap` × `card padding`.

## 4. Header e Page Header — implementado

Toda tela funcional segue a hierarquia: breadcrumb (quando necessário) →
título → descrição curta opcional → ações principais.

- Componente reutilizável: `x-ui.page-header` (props `title`, `description`,
  `breadcrumbs`, slot `actions`; aplica `aria-current="page"` no último nível).
- Aplicado no Dashboard; futuras páginas devem utilizá-lo.
- Evitar títulos soltos com margens arbitrárias.

## 5. Tipografia — implementada (sem fonte externa)

Hierarquia global em classes utilitárias (`design-system.css`, `@layer components`):
`t-page-title`, `t-section-title`, `t-card-title`, `t-body`, `t-small`, `t-muted`, `t-label`.
Família única: stack do skeleton (Instrument Sans servida no build + fallbacks
de sistema) — nenhuma fonte ou dependência externa instalada nesta Sprint.

- No máximo **uma família tipográfica principal** inicialmente.
- Não instalar fontes/dependências externas apenas por estética sem autorização.
- Pesos tipográficos usados de forma consistente.

## 6. Botões (`x-ui.button`) — implementado

Variantes: `primary`, `secondary`, `outline`, `ghost`, `danger`, `AI`.
Tamanhos: `sm`, `md`, `lg`. Todos com `hover`, `focus-visible`, `disabled`
e `loading` (spinner + `aria-busy`). Prop `full` para largura total
(formulários mobile). Renderiza `<a>` quando recebe `href`.

**Regra:** não criar estilos de botão em páginas se o componente global atender.

## 7. Formulários (`x-ui.input`, `x-ui.select`, `x-ui.textarea`) — implementado

Padronizado `input`, `select` (prop `options` + `placeholder`) e `textarea`.
Todo campo resolve erro automaticamente via `$errors`, com `label`, helper text,
`aria-invalid`/`aria-describedby`, `required` (asterisco visual + `aria-hidden`),
`disabled` e `focus-visible`. `checkbox`/`radio`/`switch` seguem o padrão nativo
com `accent-primary`; componente dedicado somente quando houver reutilização clara.

- Formulários responsivos; em tablet/mobile, priorizar **uma coluna**.
- Não usar placeholders como substituto de labels.

## 8. Cards e surfaces (`x-ui.card`, `x-ui.stat-card`) — implementado

Componentes `card` (título/descrição opcionais, slots `header`/`footer`,
padding `sm`/`md`) e `stat-card` (título, valor, hint, slot para badge).
Usam `rounded-card`, `border-border`, `shadow-card`.

- Não transformar páginas inteiras em um "card gigante" sem necessidade.
- O canvas pode permanecer visível; painéis internos formam as superfícies funcionais.

## 9. Badges e status (`x-ui.badge`) — implementado

Status semânticos: `neutral`, `success`, `warning`, `danger`, `info`, `AI`.
Não usar cores diferentes para o mesmo significado em telas distintas.

## 10. Alertas (`x-ui.alert`) — implementado

Variantes: `info`, `success`, `warning`, `danger`. Renderiza `role="alert"`.
Mensagens claras, sem expor detalhes técnicos internos.

## 11. Tabelas (`x-ui.table`) — implementado (Sprint 1)

Primeiro caso real: listagem de produtos. Props `headers`, slot com `tbody`;
células padronizadas via `.pk-table` (`design-system.css`); wrapper com scroll
horizontal próprio em telas menores, nunca overflow no body.
Em telas menores: **sem overflow horizontal do body**; se necessário, wrapper com
scroll horizontal **apenas na tabela**.

## 12. Modais (`x-ui.modal`) e `tabs` — diferidos

Sem caso de uso limpo e reutilizável nesta Sprint; criar apenas quando a
primeira necessidade real surgir, seguindo o padrão (header/body/footer,
close, focus, keyboard, overlay).
Evitar modais para tarefas que funcionem melhor em página dedicada.

## 13. Empty states (`x-ui.empty-state`) — implementado

Reutilizável: slot `icon` opcional (ilustração padrão inclusa), título,
descrição, ação opcional (`action-label` + `action-href`).
**Nunca fabricar dados** para preencher interface vazia.

## 14. Loading

Estados previstos: `loading`, `empty`, `success`, `error`, `disabled`.
Ações assíncronas sempre com feedback visual.

## 15. Responsividade (mobile-first)

| Breakpoint | Comportamento |
|---|---|
| ≥1280px | Sidebar completa, layouts amplos |
| 1024–1279px | Grids reduzidos; sidebar compacta quando necessário |
| 768–1023px | Sidebar em drawer; formulários em uma coluna; grids com menos colunas |
| <768px | Uma coluna como padrão; ações em largura disponível; cards empilhados; tabelas em wrapper próprio; sem overflow horizontal global |

Resoluções de teste: 1920, 1440, 1280, 1024, 768, 430, 390px.

## 16. Sidebar — implementada

`layouts/partials/sidebar.blade.php` compartilhada entre desktop (persistente,
248px via `w-62`) e drawer mobile (`role="dialog"`, `aria-modal`, botão fechar,
`max-w-[85vw]`). Estado ativo com `aria-current="page"`, botão do drawer com
`aria-expanded`/`aria-controls`, fechamento por Escape/clique no backdrop e
retorno de foco. Perfil do usuário + logout no rodapé. JS vanilla em
`resources/js/app.js` (sem frameworks).
Não usar ícones decorativos diferentes para a mesma ação.

## 17. Acessibilidade — baseline implementada

`:focus-visible` global (anel de 2px na cor primária), labels em todos os campos,
`aria-invalid`/`aria-describedby` nos erros (com `role="alert"`), `aria-current`,
`aria-expanded`, `aria-controls`, navegação por teclado no drawer, estados
`disabled` visíveis, `text-ink-muted` com contraste AA em superfícies claras
(`#667085`), sem dependência exclusiva de cor (badges combinam cor + texto).

## 18. Componentização — implementada (parcial)

Blade Components reutilizáveis em `resources/views/components/ui/`:
`button`, `input`, `select`, `textarea`, `card`, `stat-card`, `badge`, `alert`,
`empty-state`, `page-header` — **implementados e em uso**. `modal`, `table`,
`tabs` — **diferidos** (sem caso de uso limpo nesta Sprint).

Criar componente somente com reutilização clara ou quando parte do contrato global.

Mapeamento contrato → utilitários Tailwind v4 (**implementado**):
`text-primary`→`text-ink`, `text-secondary`→`text-ink-secondary`,
`text-muted`→`text-ink-muted` (nomes `ink-*` evitam colisão com `text-primary`
gerado por `--color-primary`, que é o acento violeta/índigo). Sidebar usa
superfície escura própria (`bg-sidebar`, `text-sidebar-ink`, `text-sidebar-muted`).
`primary-hover`/`danger-hover`/`accent-hover` existem como cores (`hover:bg-*-hover`).
Exemplo de uso (implementado):

```blade
<x-ui.page-header title="Produtos" description="Catálogo interno de afiliados">
    <x-slot:actions>
        <x-ui.button variant="primary" size="md">Novo produto</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.empty-state
    title="Nenhum produto ainda"
    description="Cadastre o primeiro produto para gerar conteúdos."
    action-label="Cadastrar produto" />
```

## 19. Regras para novas telas (checklist)

Toda nova tela deverá: usar o shell global; usar gutters globais; usar tokens;
usar componentes existentes; respeitar breakpoints; implementar estados vazios;
implementar erros; funcionar no mobile; não duplicar CSS existente.
