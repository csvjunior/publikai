# 11 — Design System, UX e Responsividade (contrato permanente)

**Estado:** contrato vigente · **Definido em:** 2026-09-29 · **Implementação:** pendente — Sprint 0.2 responsável pela implementação inicial

> Este documento é o **contrato permanente** de experiência visual do Publikai.
> Vale para todas as Sprints futuras. Nenhuma nova tela deve criar estilos,
> espaçamentos, botões, formulários ou padrões responsivos arbitrários se já
> existir solução aqui. Alterações significativas neste contrato devem ser
> registradas em `docs/10-DECISIONS.md` e no documento da Sprint correspondente.

## Situação atual (honestidade de estado)

- **Contrato: definido** (este documento).
- **Implementação: ainda não iniciada (a cargo da Sprint 0.2).** Não existe `resources/css/design-system.css`
  nem componentes em `resources/views/components/ui/` até esta data.
- As telas da Sprint 0.1 (auth, shell, dashboard) usam classes Tailwind avulsas
  e **deverão ser migradas** para tokens/componentes quando o Design System for
  implementado. Isso é pendência registrada, não conformidade.

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

Arquivo canônico: `resources/css/design-system.css` (ou estrutura equivalente
compatível com Tailwind v4). Tokens previstos:

| Grupo | Tokens |
|---|---|
| Superfície | `canvas`, `surface`, `surface-muted` |
| Texto | `text-primary`, `text-secondary`, `text-muted` |
| Borda | `border` |
| Ação | `primary`, `primary-hover` |
| Semântica | `success`, `warning`, `danger`, `info` |
| IA | `AI/accent` (quando necessário) |

Centralizar também: border radius, shadows, spacing, typography,
workspace gutters, surface padding, card padding, section gaps.

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

## 4. Header e Page Header

Toda tela funcional segue a hierarquia: breadcrumb (quando necessário) →
título → descrição curta opcional → ações principais.

- Componente reutilizável: `x-ui.page-header`.
- Evitar títulos soltos com margens arbitrárias.

## 5. Tipografia

Hierarquia global: page title, section title, card title, body, small, muted, label.

- No máximo **uma família tipográfica principal** inicialmente.
- Não instalar fontes/dependências externas apenas por estética sem autorização.
- Pesos tipográficos usados de forma consistente.

## 6. Botões (`x-ui.button`)

Variantes: `primary`, `secondary`, `outline`, `ghost`, `danger`, `AI`.
Tamanhos: `sm`, `md`, `lg`. Todos com `hover`, `focus-visible`, `disabled`
e `loading` quando aplicável.

**Regra:** não criar estilos de botão em páginas se o componente global atender.

## 7. Formulários (`x-ui.input`, `x-ui.select`, `x-ui.textarea`, …)

Padronizar `input`, `select`, `textarea`, `checkbox`, `radio` e `switch` (quando necessário).
Todo campo suporta: `label`, helper text, error, disabled, required, `focus-visible`.

- Formulários responsivos; em tablet/mobile, priorizar **uma coluna**.
- Não usar placeholders como substituto de labels.

## 8. Cards e surfaces (`x-ui.card`, `x-ui.stat-card`, …)

Componentes: `card`, `panel`, `stat card`, `empty state`.

- Não transformar páginas inteiras em um "card gigante" sem necessidade.
- O canvas pode permanecer visível; painéis internos formam as superfícies funcionais.

## 9. Badges e status (`x-ui.badge`)

Status semânticos: `neutral`, `success`, `warning`, `danger`, `info`, `AI`.
Não usar cores diferentes para o mesmo significado em telas distintas.

## 10. Alertas (`x-ui.alert`)

Variantes: `info`, `success`, `warning`, `danger`.
Mensagens claras, sem expor detalhes técnicos internos.

## 11. Tabelas (`x-ui.table`)

Header consistente, hover opcional, empty state, ações previsíveis, responsividade.
Em telas menores: **sem overflow horizontal do body**; se necessário, wrapper com
scroll horizontal **apenas na tabela**.

## 12. Modais (`x-ui.modal`)

Padrão: header, body, footer, close, focus, keyboard, overlay.
Evitar modais para tarefas que funcionem melhor em página dedicada.

## 13. Empty states (`x-ui.empty-state`)

Reutilizável. Informa: o que ainda não existe; por que é útil; próxima ação (quando aplicável).
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

## 16. Sidebar

Desktop persistente; mobile/tablet em drawer acessível. Suporta estado ativo,
grupos, itens "Em breve", ícones consistentes e navegação por teclado.
Não usar ícones decorativos diferentes para a mesma ação.

## 17. Acessibilidade

Contraste adequado, `focus-visible`, labels, `aria-current`, `aria-expanded`,
`aria-controls` quando aplicável, navegação por teclado, sem dependência
exclusiva de cor.

## 18. Componentização

Blade Components reutilizáveis em `resources/views/components/ui/`:
`button`, `input`, `select`, `textarea`, `card`, `stat-card`, `badge`, `alert`,
`modal`, `table`, `empty-state`, `page-header`, `tabs`.

Criar componente somente com reutilização clara ou quando parte do contrato global.

Exemplo de uso (quando implementado):

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
