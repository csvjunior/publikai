# Sprint 5.0 — Fundação do provider Gemini

**Período:** 2026-09-29 · **Status:** implementada, teste real pendente de credencial (sem commit/tag/push).

## 1. Pré-check e baseline

- Tree limpa; baseline: 74/74 testes, Pint passed, build OK.

## 2. Documentação oficial utilizada

- `https://ai.google.dev/gemini-api/docs/text-generation` e
  `/structured-output` e `/api-key` (vigentes em 09/2026, atualizadas 23–25/09).
- Conclusões: **Interactions API** (`POST .../v1beta/interactions`,
  `{model, input, system_instruction?, response_format?}`);
  **auth via header `x-goog-api-key`** (auth keys: padrão das novas chaves do
  AI Studio; standard irrestritas rejeitadas); **structured output** via
  `response_format {type: "text", mime_type: "application/json", schema}`;
  **`gemini-3.8-flash`** confirmado como modelo atual.
- Não foi preciso parar: a forma oficial pôde ser determinada com segurança.

## 3. Decisão de autenticação

- Auth key do AI Studio em `GOOGLE_AI_AUTH_KEY` (só `.env`).
- Nome `GEMINI_API_KEY` evitado conforme a Sprint (sugestão de legado);
  transporte idêntico ao oficial (`x-goog-api-key`).
- Sem SDK: Laravel HTTP Client suficiente; **zero dependências novas**.

## 4. Arquitetura do provider

- `AiTextProvider::generateStructured(operation, instructions, input, schema)`.
- `GoogleGeminiTextProvider` (única implementação) + binding no container
  (`AppServiceProvider`) com `match` em `config('ai.provider')`.
- Fluxo: `AiSettingsController` → `AiService::testConnection()` →
  `AiTextProvider` → Gemini API. Nenhum `Http::` direto fora do provider.
- `AiGenerationResult` (data validada, provider, model, tokens?, external id?,
  duration) — sem resposta bruta.

## 5. Configuração e variáveis

- `config/ai.php`: `provider`, `google{enabled, model, base_url, auth_key, timeout}`.
- `.env.example` (+ `.env` local, chave vazia): `AI_PROVIDER`, `GOOGLE_AI_ENABLED`,
  `GOOGLE_AI_MODEL`, `GOOGLE_AI_AUTH_KEY`, `GOOGLE_AI_TIMEOUT`. Sem segredos.

## 6. Migration

- `000010_create_ai_generations_table` (provider, model, operation, status,
  tokens?, custo?, duration?, external id?, error_code?, metadata?).
  Aplicada no MariaDB; nenhuma anterior editada.

## 7. Model/enum de logging

- `AiGeneration` (casts enum + array) e `AiGenerationStatus`
  (pending/success/failed). Sem credenciais/prompts no log.

## 8. Tratamento de erros

- Códigos: `provider_disabled`, `credentials_missing`, `unauthorized` (401/403),
  `rate_limited`, `server_error`, `timeout`, `invalid_response`, `invalid_json`,
  `schema_mismatch`, `request_failed`. Mensagens genéricas PT-BR, sem corpo técnico.
- Retry manual: 1 repetição só p/ 429/5xx; nunca 401/403/validação. Sem fila.

## 9. Structured output

- Schema `{status, message}` no teste de conexão; validação de JSON + `required`.
- Schema de Persona **não** criado (fora do escopo).

## 10. Tela/rotas IA

- `GET /settings/ai` (provider, modelo, badge Configurado/Não configurado,
  botão com `@if` externo por limitação do Blade em tags de componente) +
  `POST /settings/ai/test` (`throttle:5,1`, CSRF). Sidebar Sistema/IA ativa.

## 11. Autorização/rate limit

- `access-admin` reutilizado (sem RBAC novo): operator 403, guest login.
- Incidentes de compilação Blade documentados como lição: sem `@if`/`@disabled`
  dentro de tags de componente; pint não converge em certos encadeamentos
  (colapsados em linha única).

## 12. Testes

- `php artisan test`: **89/89 passaram** (301 assertions; 74 preservados + 15
  novos com `Http::fake`, sem rede): disabled, credencial ausente, sucesso
  (+header/model/schema), 401 sem retry (1 chamada), 429 com retry (2 chamadas),
  429→200, timeout, JSON inválido, schema mismatch, logs success/failure,
  rotas admin/operator/guest.

## 13. Pint

- **Passed** (após auto-fix + colapso de 2 cadeias de rota).

## 14. Build

- **OK** (Vite 8.3.1).

## 15. QA sem credencial

- `/settings/ai` (admin): badge **Não configurado**, botão desabilitado,
  texto orientando `.env` sem exibir chave. App íntegra (ver §19).

## 16. Passos para o responsável (credencial, sem enviá-la no chat)

1. Acessar `https://aistudio.google.com/apikey` (importar projeto se preciso).
2. Criar chave (novas já são **auth keys**) e, se standard antiga, migrar.
3. No `.env` local: `GOOGLE_AI_ENABLED=true` e `GOOGLE_AI_AUTH_KEY=<chave>`.
4. Abrir `/settings/ai` como admin e clicar **Testar conexão**.
5. Esperado: alerta "Conexão validada" (status ok) + linha `success` em
   `ai_generations`. Em falha, o `error_code` indica a causa
   (`unauthorized` = chave inválida; `timeout` = rede; etc.).

## 16b. Diagnóstico controlado (falha real sem segredo)

- QA real inicial: `server_error` persistente (HTTP 500, 2 execuções).
- Observabilidade mínima adicionada (permanente): `AiProviderException` carrega
  `httpStatus` + `details` sanitizados (`http_status`, `google_code`,
  `google_status`); códigos 500→`api_error`, 501→`unimplemented`,
  503→`service_unavailable`, 504→`deadline_exceeded`; `AiService` persiste
  `metadata` sanitizada na falha. Método temporário de diagnóstico e scripts
  externos **removidos** após uso.
- TESTE A (mínimo, sem `response_format`): 1× HTTP **200 + interaction id** →
  endpoint/model/auth **corretos**; demais tentativas 503/timeout.
- TESTE B (`AiService::testConnection`): 503 com Google
  `code: service_unavailable` e mensagem "gemini-3.8-flash is currently
  experiencing high demand... try again later".
- TESTE C (modelo temporário `gemini-3.7-flash`, override só em runtime):
  timeout + 503 idêntico (high demand) → **capacidade ampla**, sem terceiro
  modelo. Padrão `gemini-3.8-flash` preservado em `config/ai.php` e
  `.env.example`; nenhum resíduo.
- **Conclusão: sem incompatibilidade de contrato** (auth errada daria 401/403,
  payload ruim daria 400). Falha = capacidade do Google no momento.
  Nenhum código de negócio alterado; testes do mapeamento adicionados
  (`Http::fake`, sem rede).
- **Pendente (backlog formal): repetir teste real de structured output em
  janela sem service_unavailable/high demand** antes do primeiro fluxo real
  de análise.

## 17. Pendências

- Teste real com credencial (responsável).
- Confirmar campos de `usage` da Interactions API no retorno real.
- Cálculo de custo com tabela/config (futura).
- Casos de uso (análise/propostas): Sprint 5.1+.

## 18. Riscos

- Docs da API evoluem rápido (breaking de 05/2026 registrada); fixar revisão
  antes de ampliar uso.
- `GOOGLE_AI_TIMEOUT` alto + rota síncrona: só para validação manual com
  throttle; gerações futuras em fila.
- Sem SDK: mudanças de contrato da API exigem ajuste manual (teste de
  conexão detecta).

## 19. Git status

Branch `main`, alterações não commitadas (listadas na entrega). Sem tag/push.

## 20. Sugestão de commit

```
feat(ai-5.0): implement Gemini provider foundation

- AiTextProvider contract + GoogleGeminiTextProvider (Interactions API)
- AiService with sanitized ai_generations logging, admin /settings/ai
- Docs: 03 rewritten, 09/10 updated, SPRINT-05.0
```
