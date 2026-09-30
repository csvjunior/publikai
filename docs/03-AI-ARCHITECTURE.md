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

## Pendências

- Confirmar mapeamento de `usage` da Interactions API no teste real
  (extração defensiva implementada; campos exatos a validar).
- Estratégia de custo com tabela/config atualizável.
