# Sprint 5.6.0 — Video Factory foundation

## 1. Pré-check e baseline

- Branch `main`, working tree limpa no commit `1552e52`.
- Baseline: 266/266 testes, 913 assertions, Pint passed, build OK.
- FFprobe presente (`C:\ffmpeg\bin\ffprobe.exe`); sem GD (irrelevante p/ vídeo).
- Docs base relidas (02/03/04/07/08/09/10/11-DESIGN-SYSTEM + SPRINT-05.5.4).

## 2. Docs oficiais consultadas + correção dirigida (Omni Flash)

- Inicial: `https://ai.google.dev/gemini-api/docs/veo` (Veo 3.1,
  `veo-3.1-generate-preview`, `predictLongRunning` + `inlineData`, poll
  `done`, download autenticado). QA real retornou 400 `INVALID_ARGUMENT`:
  "`inlineData` isn't supported by this model" — sem operation/polling.
- Reconsulta: `https://ai.google.dev/gemini-api/docs/omni` (Gemini Omni
  Flash `gemini-omni-1.1-flash`) + `.../docs/file-input-methods` (métodos
  de input). Omni documenta oficialmente image-to-video via Interactions
  REST (`input` [{image},{text}], `response_format` video 9:16, saída em
  `steps→model_output→content[type=video]`); sem evidência oficial de
  Files→Veo, sem exemplo `file_uri` com Veo.
- Decisão (opção B): Omni Flash como provider principal — recomendação
  oficial atual p/ image→vídeo curto, REST claro, 9:16, resposta síncrona
  (async via Job), mesma infra dos providers de texto/imagem; Veo
  descartado sem hipótese (código removido, histórico no git). UMA
  implementação ativa.
- Contrato final: `generate(prompt, imageBinary, imageMime, options)` →
  binário; `input` [image, text]; sem polling/operação/download;
  `invalid_request` p/ 400 (mapeamento específico implementado).

- Histórico: primeira versão usava Veo (`predictLongRunning` + `inlineData`,
  poll `done`, download); QA real retornou o 400 acima e o contrato foi
  refeito sobre Omni (esta seção e código refletem Omni).
- Omni (`https://ai.google.dev/gemini-api/docs/omni`,
  `gemini-omni-1.1-flash`): image-to-video oficial via Interactions;
  `input` [{image},{text}] + `response_format` `{type: video, aspect_ratio}`;
  saída síncrona em `steps→model_output→content[type=video]`; 9:16 e
  resoluções 360p–4k; `generation_config.video_config.task` opcional
  (docs recomendam só prompting — omitido); mesma auth key/header.
- Sem SDK. Mesma base URL das Images/texto.

## 3. Modelo e limites

- UM modelo: `gemini-omni-1.1-flash`. Duração 8s default (`in:8`,
  metadata do request; não enviada à API); 9:16 default; MP4/WebM/MOV
  aceitos na inspeção; binário com teto 100 MB.

## 4. Migration e request

- `2026_10_06_000023_create_video_generation_requests_table` (nova;
  aplicadas intactas): source snapshot obrigatória, `operation_external_id`
  (guarda o interaction id), output, erro sanitizado, status
  pending→starting→processing→success|failed
  (`VideoGenerationRequestStatus` + `isTerminal()`).
- Tabela dedicada (ciclo próprio ≠ image requests).

## 5. Provider síncrono

- `AiVideoProvider::generate(prompt, imageBinary, imageMime, options)` +
  `GoogleOmniVideoProvider` no shape oficial; base64 só em memória.
- Resposta síncrona (sem operação/polling/download); async via Job/queue
  (tries=1, timeout 600s, HTTP 300s); sem Redis/Horizon; worker em
  08-DEPLOY.

## 6. Job, source, inspector, storage

- `GenerateVideoJob` (tries=1, timeout 600s, lock idempotente, `failed()`
  anti-preso). Source validada (missing/invalid/unsupported sem provider).
- `VideoInspector` (interface) + `FfprobeVideoInspector` (binário via
  `FFPROBE_BINARY`, Process com array, só metadata); fake explícito em teste.
- Temp `storage/app/tmp/video-generation` (sempre limpo); final
  `videos/YYYY/MM/uuid.ext`; `MediaAsset video` com `parent` = source;
  sem URL temporária persistida.

## 7. UI e integração

- Admin `/settings/ai/videos` (admin-only): provider/modelo, fila,
  source via seleção de asset existente, últimas 10 (throttle 3,1).
- Contextual `GET/POST /scripts/{script}/videos/create?source=`:
  "Criar vídeo" na gallery (só images), motion-first (prompt montado no
  POST via `VideoPromptBuilder`), 8s 9:16.
- Script detail: seção "Vídeos" (`<video controls>`, sem autoplay;
  Pendente/Iniciando/Processando/Concluído/Falhou; erros amigáveis).
  Sem pivot de imagem (request carrega `content_script_id` + output);
  purpose enum intacto.

## 8. Testes

- `VideoFactoryTest`: 12 testes (payload oficial + header, sem bloco,
  oversize, 400→`invalid_request`/429, success completo, source ×3,
  invalid_video + temp limpo, idempotência + hard failed, contextual,
  admin auth, builder + segurança, orientation).
- Total: 278/278, 978 assertions. Pint passed. Build OK.

## 9. QA

- Visual sem provider preparado (script READY + source válida + requests
  nos estados; admin + contextual + seção Vídeos; desktop + 390px).
- QA real posterior (responsável, UM clipe 8s 9:16). Sem QA real aqui.

## 9b. Microajuste pós-revisão (player, formato, CTA)

- Revisão: CTA desabilitado porque `.env` local não tem
  `GOOGLE_AI_VIDEO_ENABLED` (auth key existe; decisão humana ativar).
- Player 9:16: `MediaAsset::orientationClass()` (portrait→`aspect-[9/16]
  max-w-44`, landscape→`aspect-video`, fallback square); sem transcode.
- Formato/duração viraram read-only ("9:16 vertical", "8 segundos") com
  hidden inputs; controller fixa valores (browser não decide).
- Helper "segundo plano/alguns minutos" junto ao CTA quando configurado.

## 9c. Correção dirigida — task explícito (pós-QA real Omni)

- QA real Omni sem task: 400 `INVALID_ARGUMENT` (mesma mensagem do Veo).
- Docs oficiais (`.../docs/omni`, seção task + exemplo REST) mostram
  image-to-video inline COM `generation_config.video_config.task =
  image_to_video`; provider alinhado literalmente (só essa adição).
- Files API NÃO adotada (sem necessidade comprovada).
- `invalid_request` p/ 400 já estava correto; sem terceira chamada real
  nesta rodada.

## 9d. QA real final APROVADO + microajuste started_at/duração

- Terceira chamada (com task): request 8 `success` (~42s Job);
  `ai_generation` 25 `success` (Omni, ~35s); output asset 22
  (MP4 720×1280 ~10s, 4,2MB, `parent` = source 19); playback OK.
- Nota: request 7 também `success` real (Omni, output 21, `ai_gen` 24),
  mesma rodada QA; ambos removidos como fixtures (documentação basta).
- Microajuste: `started_at` passa a marcar início efetivo do
  processamento (era NULL no fluxo síncrono); UI não promete mais 8s
  exatos ("Clipe curto (~8–10s)"); cards success usam duração real do
  FFprobe; pendentes mostram "Clipe curto · 9:16".

## 9e. Conclusão final (fechamento)

- Veo inicialmente tentado e descartado (causa: incompatibilidade prática
  de inline image — 400 `inlineData isn't supported`).
- Omni escolhido; task explícito `image_to_video` foi decisivo.
- QA real final aprovado: MP4 720×1280 ~10s; FFprobe authoritative.
- Source lineage validado (22→19, original intacta); playback validado.
- Sem temp órfão; sem retry automático; sem FFmpeg composition;
  sem TTS/audio; sem multi-clip. Request 8 histórico mantém
  `started_at` NULL (anterior ao microajuste; sem backfill).

## 10. Pendências

- QA visual humano + QA real posterior (responsável, UM clipe). Text-to-video, multi-clip,
  composição FFmpeg, legendas, TTS, Campaign: futuros.

## 11. Riscos

- Latência real da chamada síncrona a confirmar no QA (HTTP 300s + worker
  600s com margem; sem streaming de progresso).
- Worker longo compartilha fila `database` (sem fila dedicada nesta Sprint).

## 12. Git status

- Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 13. Sugestão de commit

```
feat(ai-5.6.0): implement video factory foundation

- Omni image-to-video via synchronous Interactions call
- Video requests, job, inspector and technical/contextual UI
- Video requests, job, inspector and technical/contextual UI
- Docs: 02/03/04/07/08/09/10 updated, SPRINT-05.6.0
```
