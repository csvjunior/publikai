# Sprint 5.6.1 — Video composition with FFmpeg

## 1. Pré-check e baseline

- Branch `main`, working tree limpa no commit `b5cccc1`.
- Baseline: 280/280 testes, 987 assertions, Pint passed, build OK.
- FFmpeg 9.0.2 + libx264 confirmados; FFprobe presente.
- Docs base relidas (02/03/04/07/08/09/10/11-DESIGN-SYSTEM + SPRINT-05.6.0).

## 2. Escopo e formato fixo

- Multi-input (1–10), trim por vídeo (s/ms), imagem como segmento
  (default 3s, 1–10s), ordem por position, sem drag-and-drop.
- Saída fixa: MP4/H.264/yuv420p 720×1280 9:16 30fps, sem áudio
  (mixagem futura). Sem transições/legendas/TTS/overlays.

## 3. Migrations e modelos

- `2026_10_08_000024_create_video_composition_requests_tables` (nova;
  aplicadas intactas): requests (sem provider/model) + inputs (position
  única, trim ms, duração ms). `MediaAssetSource::Composed` (sem
  `ai_generated` p/ local).
- `VideoCompositionRequest` (inputs ordenados, output) +
  `VideoCompositionInput` (snapshot). Proveniência multi-input nos
  inputs + output id (sem parent único, sem grafo).

## 4. FFmpeg e Job

- `VideoComposer` (interface) + `FfmpegVideoComposer`: normalização
  (scale+pad sem distorção, fps, H.264, `-an`) + concat demuxer (copy);
  Process com array (Windows/Linux); `normalizeArguments()` testável.
- `VideoCompositionService` (validação snapshot, plano, temp isolado
  sempre limpo, output validado 720×1280 mp4 dur>0) + `ComposeVideoJob`
  (tries=1, 300s, lock idempotente, `failed()` anti-preso).

## 5. UI e erros

- "Criar composição" na seção Vídeos; composer server-rendered (checkbox,
  position, trim s, duração s, resumo textual implícito); POST valida e
  despacha. Cards com badge "Composição" + player orientado + erros PT.
- Códigos: source_missing/invalid, ffmpeg_failed, invalid_output, timeout
  (+ 422 de validação no create); stderr nunca no banco.

## 6. Testes

- `VideoCompositionTest`: 11 testes (domínio, trims, imagem, plano/args,
  Job success/storage, ffmpeg_failed + idempotência, UI/auth, shell
  injection). Total: 291/291, 1048 assertions. Pint passed. Build OK.

## 7. QA local real (FFmpeg permitido)

- Fixtures [QA]: script 16 READY + 2 mp4 sintéticos landscape 640×480
  (3s/2s) + 1 imagem; composição real executada localmente.
- Resultado: request success em ~5s; output MP4 720×1280, 7,0s
  (2+3+2 exatos), h264/yuv420p/30fps, só trilha vídeo (sem áudio),
  ~220KB em `videos/compositions/`; player + badge "Composição" na
  gallery; mp4 serve 200. Pad sem distorção por construção (prints
  humanos confirmam visual).
- Comando (resumo sanitizado): normalize por segmento
  (`scale+pad+fps`, libx264, `-an`) + concat demuxer (copy).

## 7b. Microajuste UX + preview de vídeo na gallery (pós-revisão)

- Revisão: labels `#id` técnicos + ausência de resumo + vídeos do pivot
  renderizados como `<img>` quebrada (gallery assumia só imagens).
- Correção: labels amigáveis ("Vídeo · 3s", "Imagem · 768×1376", IDs só
  no form); helper de posição; resumo textual com JS vanilla (checkbox,
  posição, trim, duração; ex. "1. Vídeo — 0s a 2s"); gallery renderiza
  `<video>` p/ assets vídeo (só imagens têm Criar variação/vídeo).

## 7c. Conclusão final (fechamento)

- Composição local com FFmpeg validada: request 1 `success`, inputs
  [23:0–2000ms, 25:3000ms, 24:0–2000ms], planejado 7000ms; output 26
  (MP4 720×1280 7s, h264/yuv420p/30fps, só vídeo, ~220KB).
- Trim validado; imagem como segmento validada; concatenação real;
  proveniência multi-input íntegra (sem parent único); resumo textual e
  labels sem IDs na UI; gallery por tipo; sem timeline, TTS, legenda
  ou música. Fixtures [QA] removidos (documentação acima suficiente).

## 8. Pendências

- QA visual humano. Transições, legendas, áudio/TTS, Campaign: futuros.

## 9. Riscos

- FFmpeg real só no QA local desta Sprint; suíte usa fakes.
- Vídeos de entrada heterogêneos normalizados com pad (sem distorção).

## 10. Git status

- Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 11. Sugestão de commit

```
feat(ai-5.6.1): implement video composition with FFmpeg

- Local multi-input composition with trim and fixed output
- Composition requests, job, composer UI and provenance
- Docs: 02/04/07/08/09/10 updated, SPRINT-05.6.1
```
