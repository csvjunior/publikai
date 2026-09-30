# Sprint 5.4 — Script Studio

**Período:** 2026-09-30 · **Status:** concluída, **revisão visual humana aprovada**, commit + push realizados no fechamento.

## 1. Pré-check e baseline

- Tree limpa; baseline: 139/139 testes, Pint passed, build OK.

## 2. Migration/model/enums

- `000014_create_content_scripts_table` (contexto obrigatório, texto, source,
  provider/model, erro sanitizado, approved_at) + `000015` (hook/body/cta
  nullable p/ falhados; fluxos válidos exigem via validação).
- `ContentScript` (5 belongsTo, `isReady/isApproved/isFailed/isEditable`),
  `ContentScriptStatus` (6 estados), `ContentScriptSource` (manual/ai),
  factory sem seeders. Nenhuma anterior editada (000015 é nova, conforme regra).

## 3. Schema e instructions

- `ContentScriptSchema` (title/hook/body/cta/duration! exigidos; resto opt) +
  `instructions()` versionáveis (originalidade, sem claims/preços inventados,
  sem cópia literal, CTA sem links, duração alvo).

## 4. Context resolver e service

- `resolveLocale()`: Product como base; conflito forte (>1 distinto, ambos
  preenchidos) rejeita com `ConflictingScriptContextException` → flash.
- `ContentScriptService`: createManual (draft), generate (registro único na
  conclusão: ready/failed), revise (só draft/ready, 409 senão), markReady
  (só draft), approve (só ready + approved_at), slug único.

## 5/6. Manual flow, AI flow, failed, retry, review, ready, approval

- Manual: contexto + editor → draft editável → ready → approved.
- IA: contexto → generate → ready|failed sanitizado; retry = novo registro;
  duplicata de submit mitigada com `data-once` (JS vanilla).
- Revisão não toca contexto (hidden inputs validados); aprovado não edita
  (form vira empty-state + 409 em tampering).

## 7. Rotas/controller

- `scripts.index/create/store/generate/show/edit/update/ready/approve` sob
  `auth`; controller fino; `updateStatus` p/ archived (mesma matriz).

## 8/9. Index, create, detail e sidebar

- Index (tabela 8 colunas, empty-state); create (manual + IA lado a lado em
  cards, sem wizard); show (Contexto c/ ações por status, Roteiro em seções,
  Produção, failed amigável); sidebar Roteiros ativa (após Avatares).

## 10. Factory e testes

- **158/158 passaram** (546 assertions; 139 preservados + 19 novos, sem rede).
  Incidente: placeholder `generating` violava NOT NULL → registro único na
  conclusão + colunas nullable via migration nova.

## 11/12. Pint e build

- Pint **passed**; build **OK** (Vite 8.3.1).

## 13. QA manual

- Servidor local + sessão autenticada: index/create/show/edit 200, exemplo QA
  (Curiosity Beauty Discovery) via POST, seções e `15s` no markup. Dados QA
  removidos (roteiro + usuário). Sem browser desktop; responsividade por classes.

## 14. Documentação

`02-DATABASE.md` (tabelas), `03-AI-ARCHITECTURE.md` (Script Studio),
`04-CONTENT-PIPELINE.md` (roteiros), `09-ROADMAP.md` (Sprint 5.4),
`10-DECISIONS.md` (decisão 29), este arquivo. `11-DESIGN-SYSTEM.md` inalterado.

## 15. Pendências

- QA visual humano (index, create duplo, show, edit, approve) — **aprovado**:
  detail como artefato de produção; Contexto/Roteiro/Produção separados;
  listagem respeita `isEditable()`.
- Reabertura de aprovado, fila p/ IA, Script↔Campaign, Video Factory: futuros.

## 15b. Regra de editabilidade e nullable (fechamento)

- `isEditable()` = draft|ready → Ver+Editar; approved/failed/generating/
  archived → só Ver (backend 409 + teste por status).
- hook/body/cta nullable: falhados AI sem texto persistem; fluxos válidos
  exigem via validação/domínio (sem relaxamento funcional).

## 15b. Microcorreção (pré-commit): ações da listagem respeitam `isEditable()`

- Causa: index exibia "Editar" incondicionalmente, inclusive p/ approved
  (backend já retornava 409 via `revise()`). Correção: `@if ($script->isEditable())`
  — draft/ready com Ver+Editar; approved/failed/generating/archived só Ver.
- Teste por status (URLs de show/edit) garante a regra no markup.

## 16. Riscos

- `data-once` desabilita o botão no submit: se a validação falhar, o redirect
  recarrega com botão ativo (sem estado preso). Sem JS desabilitado, duplo
  clique pode duplicar — aceito (retry cria novo por desenho).
- Síncrona + timeout 10s: OK p/ volume atual.

## 17. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 18. Sugestão de commit

```
feat(studio-5.4): implement content script studio

- ContentScript manual + AI (structured text, review, approval)
- Context validation, slug, archived rule, sidebar Roteiros
- Docs: 02/03/04/09/10 updated, SPRINT-05.4
```
