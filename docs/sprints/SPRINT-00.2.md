# Sprint 0.2 — Design System global, UX e responsividade

**Período:** 2026-09-29 · **Status:** concluída, **revisão visual humana aprovada**, commit + push realizados no fechamento.

## 1. Auditoria inicial (antes de alterar)

- Contrato lido: `docs/11-DESIGN-SYSTEM.md` (tokens + 13 seções de componentes).
- `resources/views`: 2 layouts (`guest`, `app`), 1 partial de sidebar, 4 telas de
  auth, 1 dashboard. **Nenhum componente Blade** (`components/` inexistente).
- `resources/css/app.css`: Tailwind v4 + `@theme` só com fonte; sem tokens.
- `resources/js/app.js`: vazio (`//`); drawer via `<script>` inline no layout.
- `package.json` / `vite.config.js`: Vite + Tailwind v4 + fontes Bunny (Instrument
  Sans servida no build); nada a instalar.
- Problemas encontrados: classes de input repetidas nas 4 telas de auth (mesma
  string longa ×7); sidebar `w-64` (256px vs 248px do contrato); drawer sem
  `aria-expanded`/`aria-controls`, sem fechar com Escape, sem retorno de foco;
  `text-slate-400` em superfícies claras (contraste insuficiente); título e
  margens por tela sem padrão; `bg-slate-900` do logout duplicado.
- Nenhuma funcionalidade substituída; regras, controllers, validações, rate
  limiting, registration code e roles intactos.

## 2. Direção visual aplicada

Base neutra clara (`canvas` #eef1f6, superfícies brancas), acento provisório
violeta/índigo (`primary` #4f46e5, IA `accent` #7c3aed), semânticas só com função.
Sidebar escura própria (`#0f172a`). Sem dark mode, sem lib UI, sem fonte nova.

## 3. Tokens implementados (`resources/css/design-system.css`)

Importado por `app.css` (pipeline Tailwind v4 inalterado — sem nova estrutura).
`@theme`: `canvas/surface/surface-muted/border`, `ink/ink-secondary/ink-muted`
(mapeiam `text-primary/secondary/muted` do contrato; `ink-*` evita colisão com
`text-primary` do acento), `primary(+hover/soft)`, `success/warning/danger/info`
(+`soft`/`border`, `danger-hover`), `accent(+hover/soft/border)`,
`sidebar(+hover/ink/muted)`, `--radius-card` (1rem), `--shadow-card`,
`--sidebar-width` (248px), `--workspace-max` (1440px). Gutters responsivos
(`--gutter`: 16/20/24/32px) + `.pk-workspace`. Base: body com tokens e
`:focus-visible` global. Tipografia: `t-page-title`, `t-section-title`,
`t-card-title`, `t-body`, `t-small`, `t-muted`, `t-label`.

## 4. Componentes criados (`resources/views/components/ui/`)

`button` (6 variantes, `sm/md/lg`, `hover/focus-visible/disabled/loading` com
spinner + `aria-busy`, prop `full`, renderiza `<a>` com `href`), `input`,
`select` (`options` + `placeholder`), `textarea` (erro via `$errors`,
`aria-invalid`/`aria-describedby`, `role="alert"` no erro, `required` com
asterisco, helper), `card` (slots `header`/`footer`, padding `sm`/`md`),
`stat-card`, `badge` (6 variantes), `alert` (`role="alert"`), `empty-state`
(slot `icon`, ação opcional), `page-header` (`breadcrumbs` com
`aria-current="page"`, slot `actions`). **Diferidos** (sem caso de uso):
`modal`, `tabs`, `table`.

## 5. Arquivos principais alterados

- Criados: `resources/css/design-system.css`, `resources/views/components/ui/*.blade.php` (10).
- Alterados: `resources/css/app.css` (importa DS, `@theme` de fonte movido),
  `resources/js/app.js` (drawer vanilla: `aria-expanded`, backdrop, Escape,
  foco), `layouts/app.blade.php` (sidebar 248px `w-62`, `.pk-workspace`,
  sem logout duplicado no header), `layouts/partials/sidebar.blade.php`
  (perfil + logout no rodapé, `aria-current`, botão fechar no drawer),
  `layouts/guest.blade.php` + 4 telas de auth (componentes; `novalidate` para
  validação 100% servidor), `dashboard.blade.php` (`page-header`, `card` +
  `empty-state`, 3 `stat-card` com badge "Ainda sem dados", valores "—"),
  `tests/Feature/Auth/AuthenticationTest.php` (+4 asserções estruturais).
- **Sem** migration, sem alteração de banco, auth, controllers ou rotas.

## 6. Mudanças no shell

Gutters no shell (`.pk-workspace`, 16→32px), `max-width` 1440px, sidebar 248px
persistente no desktop e drawer acessível no mobile/tablet, canvas visível
(sem "card gigante"), header slim com título + usuário/papel.

## 7. Comportamento por breakpoint

- ≥1280px: sidebar completa; dashboard em 3 colunas (`xl:grid-cols-3`).
- 1024–1279px: dashboard 2 colunas; sidebar completa (drawer só <1024 via `lg:`).
- 768–1023px: drawer; dashboard 2 colunas (`sm:`); auth 2 colunas p/ senha.
- <768px: coluna única; botões de formulário full-width; drawer `max-w-[85vw]`;
  sem overflow horizontal global (`min-w-0` + `truncate`, sem larguras fixas).
- Resoluções 1920/1440/1280/1024/768/430/390: validadas por inspeção de classes
  + renderização HTTP (sem browser desktop conectado nesta sessão — ver riscos).

## 8. Acessibilidade implementada

`:focus-visible` global, labels em todos os campos, erros com
`aria-invalid`/`aria-describedby` + `role="alert"`, `aria-current`/`aria-expanded`/
`aria-controls`, drawer navegável por teclado (Escape, foco no abrir/fechar),
`disabled` visível, `ink-muted` AA (`#667085`), badges cor + texto.

## 9. Testes / 10. Pint / 11. Build

- `php artisan test`: **13/13 passaram** (37 assertions; teste de dashboard
  estendido com `aria-current`, `aria-expanded`, "Ainda sem dados").
- `./vendor/bin/pint --test`: **passed**.
- `npm run build`: **OK** (Vite 8.3.1; utilitários e variantes verificados no
  bundle: `w-62`, `rounded-card`, `hover:bg-primary-hover`, `sm/md/xl` etc.).
- Nenhum framework de teste visual instalado.

## 12. QA manual

Percorridas via servidor local (`php artisan serve`): `/login` (200, tokens no
markup), `/register`, `/forgot-password`, `/reset-password` (render OK nos
testes de POST + markup migrado), `/dashboard` (200 autenticado, page-header,
3 stat-cards, empty-state), `/logout` (redirect, coberto por teste).
Desktop/tablet/mobile: verificados estaticamente (classes responsivas,
drawer, `novalidate` + erros servidor).

**Revisão visual humana (responsável técnico, 2026-09-29): APROVADA.**
Validados: tela de cadastro; dashboard desktop; sidebar; cards; hierarquia
tipográfica; gutters; layout global; drawer mobile; dashboard mobile; ausência
de overflow horizontal perceptível; consistência visual geral.

## 13. Pendências

- QA visual complementar nos breakpoints com browser (revisão).
- Componentes `modal`/`tabs`/`table` quando surgir o primeiro caso real.
- Componente dedicado `checkbox`/`radio`/`switch` se houver reutilização.
- Incidente de compilação Blade documentado abaixo (resolvido).

## 14. Riscos

- **Incidente resolvido:** `@php(...)` inline não é suportado pelo Blade e foi
  emitido literalmente, quebrando a compilação da sidebar (`Undefined variable
  $sections`). Corrigido para bloco `@php…@endphp`; suíte voltou a 13/13.
  Lição: usar sempre a forma de bloco.
- Tailwind escaneia `storage/framework/views` (views compiladas): após limpar
  views (`view:clear`), rodar ao menos um acesso/teste antes do build para que
  todas as classes sejam detectadas (feito nesta Sprint).
- `w-62` depende da escala dinâmica de spacing do Tailwind v4 (15.5rem = 248px);
  verificado no bundle final.

## 15. Estado do Git

Branch `main`, sem remote, sem tag, sem push. Alterações não commitadas
(aguardando revisão): 11 arquivos modificados + `design-system.css` e
`components/ui/` novos. `.env`, `vendor`, `node_modules`, `public/build`
permanecem ignorados.

## 16. Sugestão de mensagem de commit

```
feat(design-0.2): implement Publikai global design system

- Tokens in resources/css/design-system.css (Tailwind v4 @theme)
- 10 Blade components in resources/views/components/ui/
- Shell (248px sidebar, workspace gutters, accessible drawer), auth screens and dashboard migrated
- Docs: 11-DESIGN-SYSTEM implemented, 01/10 updated, SPRINT-00.2
```
