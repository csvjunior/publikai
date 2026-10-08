# Sprint 5.6.2 — Voice/TTS + audio pipeline

## 1. Pré-check e baseline

- Branch `main`, working tree limpa no commit `a983b32`.
- Baseline: 291/291 testes, 1055 assertions, Pint passed, build OK.
- Docs base relidas (02/03/04/07/08/09/10/11-DESIGN-SYSTEM + SPRINT-05.6.1).

## 2. Docs oficiais consultadas

- `https://ai.google.dev/gemini-api/docs/speech-generation` (TTS):
  modelo `gemini-3.8-flash-tts` (single-speaker, fidelidade); Interactions
  REST com `x-goog-api-key`; `input` [user_input+speech_metadata],
  `response_format` audio (WAV RIFF direto em unary), `generation_config`
  `speech_config` (vozes prebuilt; sem design/replication nesta Sprint);
  saída em `steps→model_output→content[type=audio]`; 130+ idiomas com
  detecção automática; sem SDK.

## 3. Modelo, vozes e limites

- UM modelo: `gemini-3.8-flash-tts`. 8 vozes prebuilt oficiais em config
  (Kore default); idioma auto-detectado (sem parâmetro forçado);
  texto 10–2000 (guarda interna, sem truncar); WAV fixo; timeout 120s.

## 4. Migration e request

- `2026_10_08_000025_create_audio_generation_requests_table` (nova;
  aplicadas intactas): texto funcional p/ o Job, voz, idioma, estilo,
  output, erro sanitizado, pending→processing→success|failed.

## 5. Provider, builder, Job

- `AiAudioProvider::generate(AiAudioGenerationInput)` +
  `GoogleGeminiAudioProvider` no shape oficial; base64/texto só em memória.
- `NarrationTextBuilder` determinístico (hook+body+CTA); voz do catálogo;
  tom da Persona como style (sem físico/identidade); sem cloning.
- `GenerateAudioJob` (tries=1, timeout 180s, lock, `failed()` anti-preso).

## 6. Inspector, storage, UI

- `AudioInspector` + `FfprobeAudioInspector` (duração/rate/channels);
  fake explícito em teste. `audio/YYYY/MM/uuid.wav`; `MediaAsset audio`.
- Admin `/settings/ai/audio` (admin-only) + contextual
  `GET/POST /scripts/{script}/audio` ("Gerar narração"); seção
  "Narrações" (`<audio controls>`, sem autoplay; erros PT).
- `ai_generations.audio_generation` (voz/idioma/rate; sem texto/binário).

## 7. Testes

- `AudioFactoryTest`: 9 testes (payload oficial + header, erros 400/429/
  503, builder + limites + voz, success completo, failure + idempotência,
  contextual, admin auth, segurança sem texto/base64/chave).
- Total: 300/300, 1117 assertions. Pint passed. Build OK.

## 8. QA

- Visual sem provider preparado (script READY + requests nos estados +
  WAV sintético local via ffmpeg; admin + contextual + Narrações;
  desktop + 390px). QA visual humano: aprovado.

## 8b. QA real APROVADO + observação operacional

- Request 5 `success` (Kore, en-US, ~9s; `ai_gen` 26; WAV 8,64s/24kHz/
  mono, asset 28; playback OK).
- Request 6 criado com worker parado → pending; após `queue:work`,
  `success` (asset 29, 7,84s; `ai_gen` 27); jobs 0/0.
- Web ≠ processamento síncrono: worker é requisito operacional; pending
  por worker parado não é erro (registrado para o fechamento).
- Sem cloning/lip-sync/merge/música/legenda. Fixtures [QA] removidos
  no fechamento (documentação acima suficiente).

## 9. Pendências

- QA visual humano + QA real posterior. Merge áudio+vídeo, legendas,
  música, multi-voz, Campaign: futuros.

## 10. Riscos

- Latência real a confirmar no QA (HTTP 120s + Job 180s).
- WAV pode ser grande p/ textos longos (teto 2000 chars mitiga).

## 11. Git status

- Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 12. Sugestão de commit

```
feat(ai-5.6.2): implement voice TTS and audio pipeline

- Script narration with official synthetic voices
- Audio requests, job, inspector and playback UI
- Docs: 02/03/04/07/08/09/10 updated, SPRINT-05.6.2
```
