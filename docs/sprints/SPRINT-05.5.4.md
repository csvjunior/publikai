# Sprint 5.5.4 — Controlled image editing / image-to-image

## 1. Pré-check e baseline

- Branch `main`, working tree limpa no commit `f7a1668`.
- Baseline: 251/251 testes, 860 assertions, Pint passed, build OK.
- Docs base relidas (02/03/04/07/09/10/11-DESIGN-SYSTEM + SPRINT-05.5.3).

## 2. Docs oficiais e limites

- `https://ai.google.dev/gemini-api/docs/image-generation`
  (text-and-image-to-image, já consultada nas 5.5.2/5.5.3): input como
  blocos text+image; source(1) + refs(≤4) = 5 imagens, dentro do teto do
  `gemini-3.1-flash-image`; sem SDK, Laravel HTTP Client.

## 3. Migration e parent relation

- `2026_10_04_000022_add_image_edit_support` (nova; aplicadas intactas):
  `media_assets.parent_media_asset_id` + `image_generation_requests.
  source_media_asset_id` (nullable, `nullOnDelete`; identifiers ≤54 chars).
- `MediaAsset::parent()/children()/isVariation()`;
  `ImageGenerationRequest::sourceImage()`.

## 4. Provider, builder, Job

- Contrato `generate(prompt, options, refs, ?source)`; Google monta
  text+source+refs (sem imagens → string textual intacta); base64 só no HTTP.
- `ImageEditPromptBuilder` determinístico (pedido + contexto + SOURCE vs
  REFERENCE explícito + guardrails); sem IA textual.
- Mesmo `GenerateImageJob`; `process()` com source → operation `image_edit`,
  output com `parent_media_asset_id`; sem source → fluxo atual.
- `source_missing`/`source_invalid` (+ refs iguais à 5.5.3) sem provider.

## 5. Rotas, controller, UI

- `GET/POST .../images/{mediaAsset}/edit` (`scripts.images.edit[.store]`);
  policy do Script; source validada (pertence, image pronta, 404/422).
- Tela "Criar variação": preview "Imagem base", contexto, "Alteração
  desejada", refs reutilizadas (DNA-only desabilita, source independe),
  aspect default da source, purpose default do vínculo, primary opt-in.
- Gallery: "Criar variação" por card + badge "Variação"; falhas com
  mensagens amigáveis (4 códigos novos).

## 6. Testes

- `ImageEditTest`: 15 testes (auth/source/draft/change, GET/POST, Job
  edit+derivação, source-only, source+refs+ordem, missing/invalid, primary,
  sem base64, builder, gallery). Total: 266/266, 913 assertions.
  Pint passed. Build OK.

## 7. QA

- Visual sem Google preparado (script com asset original; tela Criar
  variação; desktop + 390px). QA visual humano posterior: aprovado.

## 7b. Incidente QA — previews quebrados (causa: fixture, não código)

- QA visual detectou broken image em `/scripts/14` e na tela de variação.
  Diagnóstico: arquivos fixture [QA] eram PNGs com IHDR forjado (70 bytes;
  `getimagesize` aprovava headers, mas IDAT inválido quebrava decode no
  browser). Storage link e URLs estavam corretos (controle com asset real
  serviu 200).
- Correção só-fixture: bytes reais válidos copiados (leitura) para os paths
  dos fixtures 16/17 (+lisura `.jpg`, MIME/size atualizados nas linhas QA);
  asset 1 da 5.5.0 intocado. Nenhuma alteração de código; suíte segue verde.
- Lição: `getimagesize` não prova renderização; fixtures visuais precisam
  de bytes decodificáveis de verdade.

## 7c. QA real de edição (responsável, 2026-10-06)

- Request 11 `success` (~20s): source snapshot 17, `scene`, non-primary,
  9:16/1K/JPEG; refs snapshot [16] (source e refs tratados separadamente;
  DNA-only desmarcado).
- `ai_generation` 21: `image_edit`/`success`, `google`/
  `gemini-3.1-flash-image`, 19269ms, `source_media_asset_id: 17`,
  `reference_used: true`, `reference_count: 1` (metadata sanitizada).
- Output asset 18 (`image`/`ai_generated`, JPEG 768×1376, 600292b, válido)
  com `parent_media_asset_id = 17`; source 17 intacta (mesmos bytes, ainda
  primary do script); pivot scene/non-primary; badge "Variação" na gallery.
- Avaliação humana: original preservada, personagem consistente, botas
  preservadas, cenário alterado. QA TÉCNICO APROVADO + VISUAL CONSISTENTE.
- Sem biometria/similarity; sem canvas/máscara; sem vídeo/áudio.
- Fixtures [QA] removidos no fechamento (documentação acima suficiente).

## 8. Pendências

- QA visual humano + QA real posterior. Canvas/máscara/inpainting,
  Product/reference image, vídeo, Campaign: futuros.

## 9. Riscos

- Sem GD (binários mínimos em teste); edição real validada em QA futuro.

## 10. Git status

- Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 11. Sugestão de commit

```
feat(ai-5.5.4): implement controlled image editing

- Image variation from script gallery with source snapshot
- Gemini source+references multi-image input
- Docs: 02/03/04/07/09/10 updated, SPRINT-05.5.4
```
