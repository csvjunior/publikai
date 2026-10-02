# Sprint 5.5.1 — Image Factory contextual

**Período:** 2026-10-02 · **Status:** concluída, aguardando revisão (sem commit/tag/push).

## 1. Pré-check e baseline

- Tree limpa; baseline: 187/187 testes, Pint passed, build OK. MariaDB
  indisponível no início da sessão (sem bloqueio: testes usam sqlite;
  migrate/HTTP QA ficaram para quando o banco voltou).

## 2. Migrations e relações

- `000018_create_script_media_links` (pivot `content_script_media_assets`:
  purpose, is_primary, unique do par; + `content_script_id`/`purpose`/
  `is_primary` em `image_generation_requests`). Aplicada após o banco voltar.
- `ContentScript::mediaAssets()` / `MediaAsset::contentScripts()` (N:N);
  `ContentScript::imageRequests()`.

## 3. Purpose/primary

- `ContentScriptAssetPurpose` (cover/scene/product/background/other; default
  scene). 1 primary por roteiro (transação demote + attach/updateExistingPivot).

## 4. VisualPromptBuilder e contexto

- Determinístico, sem IA textual: SUBJECT/SCENE/PRODUCT/VISUAL STYLE/
  COMPOSITION/LIGHTING/AVATAR/CONTENT PURPOSE/COMMUNICATION/CONSTRAINTS.
- Persona = comunicação (TOM-PERSONA-XYZ nunca vira aparência — teste prova);
  Avatar = personagem artificial (`ethnicity` editorial ou ausente).
- `on_screen_text` vira "Mood hint (do not render as text)" + constraint
  "Do not render text into the image." (texto futuro no compositor/FFmpeg).

## 5. Guardrails, review, rotas, dispatch, Job, associação, primary, failure

- Guardrails visuais padrão; prompt legível (sem ids/slugs/técnico).
- `scripts/{script}/images/create|store` + `.../{asset}/primary` (operator
  permitido via policy do roteiro; técnica segue admin-only).
- Store: elegível (ready/approved) + IA configurada → request + dispatch →
  redirect p/ detail. Job reaproveitado vincula no success; failed sem pivot.
- Gallery (preview, purpose, Primary, dims, abrir) + pendentes + falhas
  recentes; empty state; CTA desabilitado p/ draft/não configurado.

## 6. Testes

- **208/208 passaram** (709 assertions; 187 preservados + 21 novos, sem rede).
  Incidentes: factory sem hair (teste, não código — Persona ignorada no
  AVATAR era o correto); `COMMUNICATION` ausente no builder (gap real,
  corrigido).

## 7/8. Pint e build

- Pint **passed**; build **OK** (Vite 8.3.1).

## 9. QA manual sem Google

- Servidor local + sessão autenticada: index/create/show 200, fluxo manual
  completo (create→show→ready→approve) + exemplo QA. Dados QA removidos.
- MariaDB caiu e voltou na sessão: migrate pendente aplicada ao voltar;
  sem perda (migrations são a fonte de verdade).
- Sem browser desktop (limitação conhecida); responsividade por classes.

## 10. Documentação

`02-DATABASE.md` (pivot + request), `03-AI-ARCHITECTURE.md` (fábrica contextual),
`04-CONTENT-PIPELINE.md` (integração), `07-SECURITY.md` (prompt só no request),
`09-ROADMAP.md` (Sprint 5.5.1), `10-DECISIONS.md` (decisão 33), este arquivo.
`11-DESIGN-SYSTEM.md` inalterado (só reuso).

## 11. Pendências (fechamento 2026-10-02)

- QA visual humano: CONCLUÍDO (desktop + mobile ~393px, sem overflow).
- QA real contextual: CONCLUÍDO (1 geração, success, ver §11d).
- Restam futuros: Reference image do Avatar, edição, vídeo, Campaign,
  Media Library.

## 11c. QA visual final (fechamento)

Desktop validado (HTTP 200): script detail, gallery compacta, estados
pending/processing/failed, tela contextual de geração, prompt legível,
opções. Mobile ~393px: contexto, detail, gallery, geração contextual,
sem overflow horizontal.

Pós-QA aplicado: gallery compactada (grid 2→5), purpose em PT
(Cena/Produto/Fundo/Capa/Outro, presentation-only), falhas amigáveis,
preview 9:16 `object-cover`, Primary identificado, "Definir como
principal" só no não-principal, "Abrir imagem" explícito.

Empty state (`/scripts/7`): "Nenhuma imagem gerada" + helper + CTA
habilitado. Draft (`/scripts/9`): seção existe, sem CTA, helper
"Marque o roteiro como pronto…", 200 sem 403/500, POST rejeitado por
teste. Approved (`/scripts/8`): Gerar imagem disponível.

## 11d. Evidência real QA contextual (documental, dados removidos no fechamento)

- Fluxo validado: Script → prompt contextual → ImageGenerationRequest →
  database queue → GenerateImageJob → Gemini → MediaAsset → pivot →
  gallery.
- Resultado: success; provider `google`; model `gemini-3.1-flash-image`;
  MIME `image/jpeg`; 768×1376 (9:16); 1K; ~711 KB; `scene`, non-primary;
  `ai_generation` success com metadata sanitizada (só mime/aspect/size).
- Sem reference image, sem edição, sem vídeo/áudio. Prompt real não
  versionado (só no request removido). Evidência documental suficiente;
  cenário [QA] removido sem deixar órfãos. Geração real da 5.5.0
  (request 1 / asset 1) preservada.

## 11b. Microcorreção — gallery compacta (pré-commit, pós-QA real)

- Problema: previews 9:16 grandes (3/linha), metadados técnicos
  (`scene 768×1376`), `timeout` cru, contador de pendentes, ação solta.
- Correção só Blade: cards compactos (`grid-cols-2→5` responsivo), purpose em
  PT via enum (`Cena`, `Produto`…), dims separadas, "Definir como principal"
  secundário, "Abrir imagem" explícito, pendentes como itens com badge,
  falhas com mensagens amigáveis + data. Teste de apresentação adicionado.

## 12. Riscos

- `updateExistingPivot` sem lock dedicado: concorrência de dois "definir
  principal" simultâneos pode intercalar (baixo risco interno; fila futura
  resolve se necessário).
- Selects duplicados nos dois cards do create (manual × IA): aceitar por
  simplicidade; unificar se confundir na revisão.

## 13. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 14. Sugestão de commit

```
feat(ai-5.5.1): implement contextual image factory

- VisualPromptBuilder + script image routes (review, dispatch, primary)
- Script↔asset pivot with purpose, gallery on script detail
- Docs: 02/03/04/07/09/10 updated, SPRINT-05.5.1
```
