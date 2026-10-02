# Sprint 5.5.0 — Image Factory foundation

**Período:** 2026-09-30 · **Status:** concluída, teste real pendente (sem commit/tag/push).

## 1. Pré-check e baseline

- Tree limpa; baseline: 159/159 testes, Pint passed, build OK.

## 2. Documentação oficial

- `ai.google.dev/gemini-api/docs/image-generation` (vigente 09/2026):
  Interactions API (`POST .../v1beta/interactions`, header `x-goog-api-key`);
  `gemini-3.1-flash-image` (Nano Banana 2); lite `...-lite-image`; pro
  `gemini-3-pro-image`; `response_format {type: image, mime_type?,
  aspect_ratio?, image_size?}`; saída em `output_image {data, mime_type?}`;
  tamanhos 1K/2K/4K (+0.5K flash; lite só 1K); ratios 1:1…21:9.

## 3. Modelo e direção

- `gemini-3.1-flash-image` configurável (`GOOGLE_AI_IMAGE_MODEL`); 9:16 default
  (Reels/TikTok/Shorts) mas por geração; 1K default (custo/latência/armazenamento).

## 4/5. Provider contract e implementação

- `AiImageProvider::generate(prompt, options)` + `GoogleGeminiImageProvider`
  (mesma auth key do texto — mesma API confirmada; sem SDK).
- `AiImageGenerationResult` (binário breve, mime, dims, tamanho, ids, duração).

## 6. Storage e MediaAsset

- Disco `public` (`storage/app/public`, link `storage:link` criado);
  `images/YYYY/MM/uuid.ext`; sem absoluto, sem base64, sem segredo.
- `MediaAsset` genérico (image/video/audio futuros) + 3 enums + factory.

## 7. Service e ai_generations

- `ImageGenerationService::generateTest()` (valida opções, chama, verifica
  binário, persiste, limpa parcial, loga `image_generation` sem prompt/base64).
- Timeout 20s/connect 5s, **sem retry** (1 tentativa < 30s).

## 8. Erros e tela admin

- Códigos sanitizados (disabled/missing/unauthorized/rate_limited/5xx/timeout/
  invalid_image). Tela `/settings/ai/images` (admin, `throttle:3,1`): provider,
  badge de config (nunca a chave), form, preview + metadata, últimas 10.
  Sidebar IA já cobre a rota.

## 9. Testes

- **172/172 passaram** (621 assertions; 159 preservados + 13 novos, `Http::fake`,
  sem rede, sem GD — fixtures PNG + JPEG construído via `pack`).

## 10/11. Pint e build

- Pint **passed**; build **OK** (Vite 8.3.1).

## 12. QA sem provider

- Página com `GOOGLE_AI_IMAGE_ENABLED=false`: badge Não configurado, botão
  desabilitado, sem erro (verificar na revisão com o `.env` local).

## 13. Habilitar geração real (responsável, sem enviar chave no chat)

1. No `.env`: `GOOGLE_AI_IMAGE_ENABLED=true` (reusa `GOOGLE_AI_AUTH_KEY`).
2. Como admin, `/settings/ai/images` → prompt do spec → **Gerar imagem de teste**.
3. Esperado: preview + metadata + linha `success` em `ai_generations`.

## 14. Pendências

- Teste real (responsável); confirmar `mime_type` de saída e `usage` no retorno.
- Integração com Script/Avatar, edição, vídeo, Campaign, Media Library: futuros.

## 14b. Microcorreção — parser REST (pré-commit)

- QA real retornou `invalid_image`: causa objetiva — parsers liam conveniências
  de SDK (`output_image`/`output_text`) ausentes no JSON REST bruto.
- Ambos reescritos: `steps[] → model_output → content[]` (último bloco válido;
  texto ignora não-texto; `output_*` só como fallback). MIME da resposta
  prevalece; `id` do root como external id; `usage` segue nullable.
- Fakes atualizados para o formato REST documentado (+ fallback legado).
- Sem nova chamada real (ordem expressa).

## 14c. Microcorreção — geração assíncrona (pré-commit)

- Sequência real: rate_limited → `invalid_image` (parser SDK) → timeout 20s
  (motivo do async) → **success**: POST ~0.5s, worker ~11s, request id 1
  `pending→processing→success`, asset id 1 JPEG **768×1376**, 809158 bytes,
  `images/2026/10/uuid.jpg`, `ai_generations success` (11240 ms, external id
  presente, tokens `NULL`), preview confirmado, jobs 0, failed 0, 0 órfãos,
  logs sem segredo/base64/raw. Evidência preservada no ambiente local
  (fora do Git).
- Hard-timeout protection `failed()` adicionada (worker SIGKILL não deixa
  `processing` preso); coberta por testes.

- Causa arquitetural: geração real excedeu 20s; request web não comporta.
- `ImageGenerationRequest` (prompt funcional, opções, provider/model, asset,
  erro sanitizado) + `GenerateImageJob` (`ShouldQueue`, `tries=1`, timeout 90s,
  lock idempotente, `internal_error` + report p/ inesperados).
- `ImageGenerationService` dividido em `createRequest()` (web, rápido) e
  `process()` (worker). Controller só enfileira; UI lista gerações.
- Queue `database` (tabelas oficiais já aplicadas); HTTP 60s/connect 5s;
  worker local `queue:work`; produção via systemd/Supervisor (sem config).
- Testes do Job executam `handle()` direto (sem daemon, sem rede).

## 15. Riscos

- Geração real pode ser lenta/cara: 1K + sem retry + throttle mitigam.
- `output_image.mime_type` pode variar — detecção local prevalece (documentado).

## 16. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 17. Sugestão de commit

```
feat(ai-5.5.0): implement image factory foundation

- AiImageProvider + GoogleGeminiImageProvider (Nano Banana 2)
- MediaAsset with safe Storage persistence, admin test page
- Docs: 02/03/07/09/10 updated, SPRINT-05.5.0
```
