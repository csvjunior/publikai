# Sprint 5.5.2 — Avatar reference image foundation

## 1. Pré-check e baseline

- Branch `main`, working tree limpa no commit `6871a86`.
- Baseline: 209/209 testes, 717 assertions, Pint passed, build OK.
- Docs base relidas (02/03/04/07/09/10/11-DESIGN-SYSTEM + SPRINT-05.5.1).

## 2. Documentação oficial consultada

- `https://ai.google.dev/gemini-api/docs/image-generation` (seção
  text-and-image-to-image + "Use up to 14 reference images"):
  `input` como blocos `[{type:text}, {type:image, mime_type, data(base64)}]`;
  `gemini-3.1-flash-image` aceita até 4 imagens de personagem para
  consistência. Payload implementado exatamente nesse shape; sem SDK.

## 3. Migrations e relações

- `2026_10_02_000019_add_avatar_reference_images` (nova, sem tocar
  aplicadas): `avatars.reference_media_asset_id` nullable (`nullOnDelete`)
  + `image_generation_requests.reference_media_asset_id` nullable
  (`nullOnDelete`). Identifiers ≤58 chars (limite MariaDB 64).
- `Avatar::referenceImage()` + `ImageGenerationRequest::referenceImage()`
  (`BelongsTo` MediaAsset).

## 4. Upload, storage, replace, remove

- `StoreAvatarReferenceRequest`: JPEG/PNG, ≤10 MB, ≥512×512, sem proporção
  imposta. `AvatarReferenceService`: validação binária real
  (`getimagesizefromstring`), `avatars/references/YYYY/MM/uuid.ext`, asset
  `uploaded` (provider/model null), UUID sem nome original.
- Replace/remove com limpeza exclusiva segura: anterior só é apagado
  (arquivo + linha) se nenhum outro Avatar/request/pivot o usa; compartilhado
  é preservado. Avatar nunca é excluído.
- Rotas `avatars.reference.create/store/destroy`; policy do Avatar
  (`update`), sem RBAC novo.

## 5. Provider, request, Job, prompt

- `AiImageReference` DTO (binary/mime/dims); contrato
  `generate(prompt, options, ?reference)` (terceiro parâmetro opcional).
- Google: sem referência → `input` string (payload 5.5.0 intacto); com
  referência → blocos text+image oficiais. base64 só no HTTP em memória.
- `createRequest` aceita `reference_media_asset_id` (snapshot do Avatar no
  `ScriptImageController::store`); Job resolve via request (troca posterior
  não afeta); `reference_missing`/`reference_invalid` sem provider call.
- `VisualPromptBuilder` adiciona instrução de consistência só quando há
  referência. `ai_generations`: `reference_used` + id (sem base64/prompt).
- UI: seção no Avatar detail (empty/preview/meta/Abrir/Substituir/Remover);
  indicador Sim/Não + thumbnail + helpers no Gerar imagem. Sem editor/crop.

## 6. Testes

- `AvatarReferenceTest`: 24 testes (upload, replace, remove, snapshot, Job,
  provider text+image via `Http::assertSent`, failures sem provider,
  segurança sem base64/absoluto, prompt, UI).
- Total: 233/233, 804 assertions. Pint passed. Build OK.

## 7. QA

- QA visual sem Google preparado (Avatar sem/com referência; empty, preview,
  upload, replace, remove, mobile 390, tela contextual). Sem QA real nesta
  etapa — UMA geração com referência fica para o responsável após revisão.

## 7b. Microcorreção — tamanho do arquivo (pós-revisão visual)

- Revisão encontrou metadata incompleta na referência ativa (dims + MIME,
  sem tamanho). Correção só Blade: `App\Support\FileSize::format()`
  (B/KB/MB, sem intl, null-safe, sem lógica de domínio) + teste de
  apresentação (formatado visível, cru ausente, null não quebra).

## 7c. QA real contextual com referência (responsável, 2026-10-03)

- Request 6: `success`, script 10, `reference_media_asset_id` 6 (snapshot no
  POST), `scene`, non-primary, 9:16/1K/JPEG, ~18s (started→completed).
- `ai_generation` 16: `success`, `google`/`gemini-3.1-flash-image`,
  16967ms, `reference_used: true`, `reference_media_asset_id: 6`
  (metadata sanitizada, sem base64/prompt).
- Output asset 7: `image`/`ai_generated`, JPEG 768×1376, 679841 bytes,
  UUID, arquivo válido; pivot script 10 ↔ asset 7 (`scene`, non-primary).
- Referência asset 6: `image`/`uploaded`, JPEG 768×1376, inalterada,
  ainda vinculada ao Avatar 10.
- Observação humana: referência continha gato usando botas; output
  preservou o mesmo conceito/personagem visual na gallery — consistência
  visual confirmada, NÃO garantia de identidade perfeita; sem comparação
  biométrica.
- O responsável não usou prompt sugerido adicional — NÃO invalida o QA
  (prompt contextual do builder + reference input bastaram); sem segundo
  teste, sem nova geração.
- Fixtures [QA] removidos no fechamento (documentação acima é suficiente).

## 8. Pendências

- QA visual humano + QA real posterior (responsável, UMA geração).
- Múltiplas referências, product/reference image input, edição, vídeo,
  Campaign: futuros.

## 9. Riscos

- Sem GD no ambiente: testes usam binários mínimos válidos (só validação,
  nunca render). Upload real validado em QA visual.
- `deleteIfExclusive` sem lock dedicado (mesmo perfil do primary 5.5.1;
  baixo risco interno).

## 10. Git status

- Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 11. Sugestão de commit

```
feat(ai-5.5.2): implement avatar reference image foundation

- Avatar reference upload (preview, replace, remove) + contextual use
- Reference snapshot per request, text+image provider payload
- Docs: 02/03/04/07/09/10 updated, SPRINT-05.5.2
```
