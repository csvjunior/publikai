# 03 — Arquitetura de IA

**Estado:** fundação implementada (Sprint 5.0); sem análise de negócio · **Atualizado em:** 2026-09-29

## Decisão atual

- **Google Gemini como primeiro (e único) provider**, via **Interactions API REST**
  (`POST {base}/interactions`), sem SDK — Laravel HTTP Client é suficiente.
- Referência oficial: `https://ai.google.dev/gemini-api/docs` (vigente em 09/2026).
- Modelo configurável via `.env` (`GOOGLE_AI_MODEL`); configuração inicial
  planejada: `gemini-3.8-flash` (confirmado disponível na documentação oficial).
- **Autenticação: Authorization (auth) key do AI Studio** (todas as chaves novas
  já são auth keys), enviada no header `x-goog-api-key`. Chaves standard
  irrestritas são rejeitadas pela API — usar sempre chave válida/restrita.
  Variável: `GOOGLE_AI_AUTH_KEY` (só no `.env`, nunca no código/logs/telas/testes).
- Structured output via `response_format {type: "text", mime_type:
  "application/json", schema}` + validação de `required` no provider.

## O que existe (Sprint 5.0)

- Contrato `App\AI\Contracts\AiTextProvider::generateStructured()` + binding
  no container (`AppServiceProvider`) — troca futura sem tocar consumidores.
- `GoogleGeminiTextProvider`: timeout configurável, retry conservador (1x só
  para 429/5xx; nunca 401/403/validação), erros sanitizados (`errorCode`,
  sem corpo técnico), parsing defensivo de `output_text`/`usage`.
- `AiGenerationResult` (sem resposta bruta) + `AiService::testConnection()`
  (orquestra chamada + log).
- `ai_generations` (provider, model, operation, status, tokens, custo
  **nullable sem cálculo**, duration, external id, error_code, metadata):
  sem credenciais, sem prompts completos. Custo real depois, com tabela/config
  atualizável — nunca fingido.
- Tela admin `Sistema → IA` (`/settings/ai`): provider, modelo, status de
  configuração (nunca a chave) + botão "Testar conexão" (chamada real mínima,
  `throttle:5,1`). Só admin (`access-admin` reutilizado); operator 403.
- Testes com `Http::fake()` (sem rede): disabled, credencial ausente, sucesso,
  401, 429 (+retry), timeout, JSON inválido, schema mismatch, logs, rotas.

## Continua sem IA de negócio (atualizado na Sprint 5.1)

- Primeira análise real: `ReferenceAnalysis` (histórico imutável) via
  `ReferenceAnalysisService` — padrões de referências, sem scraping,
  sem Persona/Avatar gerados, sem Blueprint.
- Fluxo futuro: propostas de Persona/Avatar a partir de análises → revisão
  humana → salvar (só plano).

## Sprint 5.2 — propostas assistidas (human-in-the-loop obrigatório)

- `IdentityProposal` (pending→processing→ready|failed; ready→applied|discarded)
  via `IdentityProposalService`: IA propõe Persona + Avatar + rationale curto
  a partir da latest successful; humano revisa/edita; apply cria ambos
  **active** em transação (idempotente); descarte preserva histórico.
- Guardrails: sem copiar criador real, sem estereótipos, sem inferir atributos
  sensíveis (`ethnicity_description` editorial ou null). Sem imagem/voz.

## Sprint 5.3 — Content Blueprints (manuais; IA futura)

- `ContentBlueprint`: ESTRUTURAS reutilizáveis (formato, hook, sequência, CTA,
  estilo), sem roteiro/mídia e sem vínculo Product/Persona/Avatar.
- `source_type` manual (automático) preparado p/ `ai_assisted` futuro
  (ReferenceAnalysis + Persona + Avatar + Product context → proposta → revisão).

## Sprint 5.4 — Script Studio (manual + IA, textual)

- `ContentScript` (manual draft→ready→approved; IA generating→ready|failed):
  texto estruturado a partir de Product + Blueprint + Persona + Avatar.
- `ContentScriptSchema` + instruções versionáveis; conflito forte de
  language/market rejeita a geração; CTA textual sem links.
- hook/body/cta nullable no banco (falhados sem texto); fluxos válidos exigem
  via validação. Sem vídeo/imagem/voz, sem Campaign/publicação.

## Sprint 5.5.0 — Image Factory foundation (Nano Banana 2)

- `AiImageProvider` + `GoogleGeminiImageProvider` (`gemini-3.1-flash-image`,
  mesma auth key do texto, `response_format` image, 1K/9:16 configuráveis,
  sem retry, timeout 20s): text-to-image → binário validado
  (`getimagesizefromstring`, só jpeg/png) → Storage público → `MediaAsset`.
- `ImageGenerationService` (valida opções, persiste arquivo seguro
  `images/YYYY/MM/uuid.ext`, limpa parcial, loga `image_generation` sem
  prompt/base64). Tela admin `/settings/ai/images` (preview + últimas 10).
- Modelos futuros documentados: `gemini-3.1-flash-lite-image` (volume),
  `gemini-3-pro-image` (premium). Sem fallback, sem Avatar reference, sem edição.

## Sprint 5.5.1 — Image Factory contextual (roteiros)

- `VisualPromptBuilder` (determinístico, sem IA textual): SUBJECT/SCENE/
  PRODUCT/VISUAL STYLE/COMPOSITION/LIGHTING/AVATAR/CONTENT PURPOSE/
  COMMUNICATION/CONSTRAINTS ("Do not render text into the image").
  Persona = comunicação (nunca aparência); Avatar = personagem artificial.
- Roteiro ready/approved → prompt revisável → request com `content_script_id`
  → Job reaproveitado vincula asset (primary transacional) → gallery no detail
  + "Definir como principal". Sem reference image, edição, vídeo ou Campaign.

## Microcorreção async (geração longa > request web)

- Parser REST corrigido: `steps→model_output→content` (conveniências de SDK
  não existem no REST bruto). Geração virou **assíncrona**: POST cria
  `ImageGenerationRequest` + dispatch `GenerateImageJob` (queue `database`,
  `tries=1`, timeout 90s, lock idempotente) e redireciona; worker executa com
  timeout HTTP 60s. UI lista gerações (Pendente/Processando/Concluída/Falhou).
- Produção futura: worker via systemd/Supervisor da Jaguartec (sem config aqui).

## Pendências

- Confirmar mapeamento de `usage` da Interactions API no teste real
  (extração defensiva implementada; campos exatos a validar).
- Estratégia de custo com tabela/config atualizável.
