# Sprint 5.6.3 — Merge voice + video with FFmpeg

## 1. Pré-check e baseline

- Branch `main`, working tree limpa no commit `223a7c3`.
- Baseline: 300/300 testes, 1117 assertions, Pint passed, build OK.
- libx264 + AAC nativo confirmados; FFprobe presente.
- Docs base relidas (02/03/04/07/08/09/10/11-DESIGN-SYSTEM + 05.6.1/05.6.2).

## 2. Escopo e política video_master

- 1 vídeo + 1 narração do mesmo Script; output dura o vídeo (áudio menor
  → silêncio; maior → trim); sem extender/loop; sem offset/fade/mixagem.
- Saída: MP4/H.264/yuv420p 720×1280/30fps + AAC 128k/48kHz; áudio original
  do vídeo ignorado (narração substitui). Sem legendas/lip-sync/música.

## 3. Migrations e modelos

- `2026_10_08_000026_create_audio_video_merge_requests_table` (nova;
  aplicadas intactas): snapshot vídeo+áudio, `duration_policy`,
  output, erro sanitizado, pending→processing→success|failed.
- `AudioVideoMergeRequest` (video/audio/output) + `MediaAssetSource::Merged`
  (nunca `ai_generated` p/ local) + `AudioVideoDurationPolicy::VideoMaster`.
- Proveniência oficial: os 3 IDs no request (sem parent único).

## 4. FFmpeg e Job

- `AudioVideoMerger` (interface) + `FfmpegAudioVideoMerger`: map explícito
  (0:v:0 + 1:a:0), normalização vídeo, AAC, `-t` = duração do vídeo;
  Process com array (Windows/Linux); `mergeArguments()` testável.
- `AudioVideoMergeService` (validação snapshot, plano, temp isolado
  sempre limpo, output validado: mp4 + vídeo h264 + áudio aac + dur>0) +
  `MergeAudioVideoJob` (tries=1, 300s, lock, `failed()` anti-preso).

## 5. UI e erros

- "Adicionar narração ao vídeo" na seção Vídeos; form com selects
  (durações nos labels) + comparação/resultado + warning de trim via JS;
  sem offset/volume/fade.
- Cards "Com narração" (player orientado, duração real) + erros PT;
  vídeos merged bloqueados como input (sem loops).

## 6. Testes

- `AudioVideoMergeTest`: 10 testes (domínio, policy A/B, plano/args,
  Job success/storage, failure + idempotência, sem faixa áudio, UI/auth,
  shell injection). Total: 310/310, 1170 assertions. Pint/build OK.

## 7. QA local real (FFmpeg permitido)

- Fixtures [QA]: script 18 + vídeo 10s (com faixa de áudio própria) +
  narração WAV 8s; request 2 `success` em ~6s; output MP4 720×1280,
  10,0s (video master: 8s narração + 2s silêncio), h264/yuv420p/30fps,
  trilhas `h264,video` + `aac,audio` (áudio original substituído);
  player + badge na gallery; mp4 serve 200.
- Achado do QA: `FfmpegAudioVideoMerger` não criava o workDir (request 1
  falhou `ffmpeg_failed` com mensagem amigável, sem órfãos); corrigido
  com `mkdir` + mensagem adequada. Suíte usa fakes (FFmpeg real só aqui).
- Ciclo extra humano via UI: composição #2 + merge request 3 (vídeo
  composed 33 + narração 31 → output 34), ambos `success`; confirma
  ponta a ponta; incluídos na limpeza como QA.

## 8. Pendências

- QA visual humano. Offset/fade/mixagem, legendas, lip-sync, Campaign:
  futuros.

## 9. Riscos

- FFmpeg real só no QA local; suíte usa fakes.
- Inputs heterogêneos normalizados (pad; documentado).

## 10. Git status

- Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 11. Sugestão de commit

```
feat(ai-5.6.3): implement audio video merge with FFmpeg

- Local voice-over-video merge with video-master policy
- Merge requests, job, merge UI and provenance
- Docs: 02/04/07/08/09/10 updated, SPRINT-05.6.3
```
