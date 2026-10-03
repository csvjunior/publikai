# Sprint 5.5.3 — Multi-reference Avatar consistency

## 1. Pré-check e baseline

- Branch `main`, working tree limpa no commit `76fa9e1`.
- Baseline: 234/234 testes, 808 assertions, Pint passed, build OK.
- Docs base relidas (02/03/04/07/09/10/11-DESIGN-SYSTEM + SPRINT-05.5.2).

## 2. Documentação oficial consultada

- `https://ai.google.dev/gemini-api/docs/image-generation` ("Use up to 14
  reference images", já consultada na 5.5.2): `gemini-3.1-flash-image`
  aceita até 4 imagens de personagem p/ consistência → limite funcional 4
  (`GOOGLE_AI_IMAGE_MAX_REFERENCES`, sem número mágico). Shape text+N image
  blocks confirmado; sem SDK.

## 3. Migrations e migração dos dados 5.5.2

- `2026_10_03_000020_create_avatar_reference_media_assets`: pivot
  (`is_primary`, `position`, unique curta `avatar_ref_asset_unique`, índice
  `avatar_ref_primary_idx`); migra referência singular existente
  (primary/position 1); remove `avatars.reference_media_asset_id`.
- `2026_10_03_000021_create_image_generation_request_references`: snapshot
  múltiplo (FKs curtas explícitas `igr_ref_*_fk`, unique/índice curtos);
  migra snapshot singular; remove coluna. Histórico (request 1/asset 1)
  preservado.
- Incidente: `foreignId()` não aceita nome de constraint (arg ignorado) →
  FK longa estourou limite MariaDB; parcial DDL (tabela vazia, sem FKs)
  removida com segurança e migration corrigida (`constrained(tabela, id,
  nome)`). Lição: nunca presumir assinatura; MariaDB não reverte DDL.

## 4. Relações, primary, position, limite

- `Avatar::referenceImages()` (pivot ordenada) + `primaryReferenceImage()`;
  `MediaAsset::referencedByAvatars()`; `ImageGenerationRequest::referenceImages()`.
- Primary única via transação; primeira vira primary; remover primary
  promove a primeira por position (Avatar nunca com refs e sem primary).
- Position estável (append; sem drag-and-drop). Limite via config (4).

## 5. Upload / add / remove

- Validação 5.5.2 reutilizada intacta (JPEG/PNG, 10 MB, 512×512, binário,
  UUID, path relativo, `uploaded`). POST além do limite → 422 (tela mostra
  CTA desabilitado + helper).
- Add/remove/primary por asset (`DELETE .../references/{mediaAsset}`,
  `POST .../primary`); relação validada (404 em asset alheio); limpeza
  exclusiva segura preservada. Sem fluxo "substituir".

## 6. Provider, snapshot, seleção, Job, failures

- Contrato `generate(prompt, options, list<AiImageReference>)`; REST
  text+N images (primary primeiro); base64 só no HTTP em memória.
- Snapshot múltiplo antes do dispatch; default todas; checklist com
  desmarque (primary NÃO obrigatória — decisão documentada); Visual DNA
  only = []; ID estranho → 422.
- Job único; `reference_missing`/`reference_invalid` em qualquer item
  abortam sem provider. `ai_generations`: `reference_used`/`count`/ids.
- Prompt: plural com 2+ ("reference images together"), singular com 1.

## 7. Testes

- `AvatarMultiReferenceTest`: 18 testes (limite/primary/ordem, remove +
  promoção, snapshot imutável, seleção POST, DNA only, 422 estranha,
  provider multi+ordem, missing, prompt plural, UI grid/limite/checklist).
- `AvatarReferenceTest` reescrito p/ nova API (upload, segurança, remove,
  Job, failures, prompt, UI). Total: 251/251, 856 assertions. Pint/build OK.

## 8. QA

- Visual sem Google preparado (avatars 0/1/3 refs; contextual com seleção
  e DNA only; desktop + 390px). QA real posterior (responsável, UMA geração
  com 2–3 refs). Sem QA real nesta etapa.

## 8c. QA real final representativo (responsável, 2026-10-03)

- A) QA inicial com clones (assets 8–11, bytes idênticos) validou
  infraestrutura (snapshot=3, provider=3 blocks, count=3), não consistência;
  output divergiu (mãos/moedas) — classificação A da auditoria.
- B) Auditoria confirmou integração correta; sem bug para corrigir.
- C) Duas auxiliares legítimas geradas via single-reference do asset 1:
  asset 13 (`8cca2df69d94`, 761654b) + asset 14 (`de7b16cca20d`, 562048b),
  mesmo gato, enquadramentos diferentes, aprovadas visualmente.
- D) Hashes distintos confirmados (4f5482d84f9a / 8cca2df69d94 / de7b16cca20d).
- E) Avatar 15 final: [1 primary, 13, 14]; script 13 (product 7, blueprint
  8, persona 8) com prompt controlado multi-reference.
- F) Geração final: request 10 `success` (~15s), snapshot [1,13,14].
- G) `ai_generation` 20: `success`, `google`/`gemini-3.1-flash-image`,
  14561ms, `reference_used: true`, `reference_count: 3`.
- H) Output asset 15 (JPEG 768×1376, 699726b, válido; pivot scene,
  non-primary); avaliação humana: PERSONAGEM CONSISTENTE.
- QA TÉCNICO APROVADO + QA VISUAL PERSONAGEM CONSISTENTE =
  MULTI-REFERENCE VALIDADO (auxílio de consistência, não garantia
  matemática de identidade; sem biometria/similarity).
- Fixtures [QA] removidos no fechamento (documentação acima suficiente).

## 8b. Microcorreção — Visual DNA only × referências (pós-revisão visual)

- Revisão encontrou ambiguidade: DNA-only marcado vencia no server, mas os
  checkboxes seguiam visualmente ativos. Correção só Blade/JS vanilla:
  DNA-only desabilita (`disabled` real) e atenua (`opacity-50`) os cards,
  exibe helper discreto; `checked` preservado e restaurado ao desmarcar;
  backend continua authoritative.

## 9. Pendências

- QA visual humano + QA real posterior. Product/reference image input,
  edição, vídeo, Campaign: futuros.

## 10. Riscos

- Binários mínimos em testes (sem GD); uploads reais validados em QA.
- Exclusividade sem lock dedicado (perfil 5.5.1, baixo risco interno).

## 11. Git status

- Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 12. Sugestão de commit

```
feat(ai-5.5.3): implement multi-reference avatar consistency

- Avatar multi-reference with primary, limit and safe removal
- Human selection with Visual DNA only and multi snapshot
- Gemini REST multi-image input
- Docs: 02/03/04/07/09/10 updated, SPRINT-05.5.3
```
