# Sprint 5.2 — AI Persona & Avatar Proposals

**Período:** 2026-09-30 · **Status:** concluída, aguardando revisão (sem commit/tag/push).

## 1. Pré-check e baseline

- Tree limpa; baseline: 111/111 testes, Pint passed, build OK.

## 2. Migration/model/enum

- `000012_create_identity_proposals_table` (`identity_proposals`: profile FK
  cascade, analysis FK nullable, status, provider/model, persona/avatar/
  rationale JSON, error sanitizado, created_by, applied_* + applied_at,
  started/completed_at; índice (profile, status)). Aplicada; anteriores intactas.
- `IdentityProposal` (casts, 4 belongsTo, `isReady()`),
  `IdentityProposalStatus` (6 estados + labels/badges),
  `IdentityProposalFactory`. `ReferenceProfile::identityProposals()` (latest).

## 3. Schema e instructions

- `IdentityProposalSchema::schema()` (persona/avatar mapeando o domínio exato,
  sem `status`; rationale `{persona[], avatar[]}`) + `instructions()`
  versionáveis (guardrails: sem copiar criador real, sem estereótipos, sem
  inferir atributos sensíveis, `ethnicity_description` editorial ou null).

## 4. Service

- `IdentityProposalService::generate()` (elegibilidade: perfil ativo +
  latestSuccessful + IA configurada; pending/processing bloqueia; input só
  cadastrado; valida códigos + estrutura; ready/failed sanitizado).
- `revise()` (só dados), `apply()` (transação + `lockForUpdate`, cria ambos
  **active**, idempotente), `discard()` (só ready). Exceções de elegibilidade
  reutilizadas com mensagens próprias.

## 5/6. Geração, failed, edição, apply, idempotência, discard

- Cobertos pelo service + testes (ver §13). Apply em transação; duplo clique
  não duplica (lock + checagem); descarte preserva registro e dados.

## 7. Rotas/controller

- `references.proposals.store` (throttle) / `update` / `apply` / `discard`,
  aninhadas com `scopeBindings` (404 cross-profile). Controller fino;
  `UpdateIdentityProposalRequest` compõe regras de `StorePersona/AvatarRequest`
  com prefixos (sem `status`).

## 8/9. UI e rationale

- Seção "Identidade sugerida" no detalhe: estados (sem análise / desconfigurada
  / gerar / pronta / falha / aplicada), Persona + Avatar lado a lado (empilha
  mobile), rationale em bullets ("Por que a IA sugeriu isso?"), sem JSON.
- Revisão em `<details>` com form agrupado (PERSONA/AVATAR); apply/discard com
  confirmação no descarte; aplicada mostra "Ver Persona/Ver Avatar".

## 10. Histórico e autorização

- Histórico newest-first (data, status, provider/model, nomes); destaque
  latest ready → applied → latest. Ambos os papéis (mesma regra References).

## 11/12. Factory e testes

- Factory sem seeders. **127/127 passaram** (444 assertions; 111 preservados +
  16 novos, `Http::fake`, sem rede).

## 13/14. Pint e build

- Pint **passed**; build **OK** (Vite 8.3.1).

## 15. QA manual

- Servidor local + sessão autenticada: seção, geração (fake? não — fluxo com
  provider desabilitado no QA? registrar). Sem browser desktop; responsividade
  por classes.

## 16. Documentação

`02-DATABASE.md`, `03-AI-ARCHITECTURE.md`, `09-ROADMAP.md`, `10-DECISIONS.md`
(decisão 27), este arquivo. `11-DESIGN-SYSTEM.md` inalterado (só reuso).

## 17. Pendências

- QA visual humano (seção, revisão, histórico).
- Teste real de structured output (backlog 5.0) validaria o caminho completo.
- Blueprint/roteiros/imagem/voz: futuros.

## 18. Riscos

- Execução síncrona + timeout 10s: OK p/ volume atual; fila se justificar.
- Rationale da IA pode variar em qualidade; revisão humana é obrigatória.

## 18b. Microcorreção UX (pré-commit)

- Risco confirmado: revise (PUT) e apply (POST) são forms separados; apply usa
  dados persistidos, então edits não salvos seriam ignorados silenciosamente.
- Proteção vanilla em `app.js`: snapshot do form de revisão; qualquer
  mudança desabilita "Aplicar proposta" + exibe helper (com `role="status"`);
  após salvar, o reload limpa o estado. `beforeunload` só quando dirty.
  Sem abrir o form, apply funciona normalmente; Descartar segue disponível.

## 19. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 20. Sugestão de commit

```
feat(ai-5.2): implement AI persona and avatar proposals

- IdentityProposal with human-in-the-loop (generate/review/apply/discard)
- Versioned schema/instructions with guardrails, transactional apply
- Identity section on reference detail with rationale and history
- Docs: 02/03/09/10 updated, SPRINT-05.2
```
