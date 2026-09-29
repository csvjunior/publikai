# 09 — Roadmap

**Estado:** planejamento · **Atualizado em:** 2026-09-29

## Sprint 0.1 — Fundação técnica (concluída)

- Identidade Publikai, autenticação interna, shell admin responsivo,
  dashboard inicial, campo `role`, docs base, README, testes e build.

## Sprint 0.2 — Design System (concluída)

- Tokens centralizados, 10 componentes Blade, shell/auth/dashboard migrados,
  revisão visual aprovada.

## Sprint 1 — Produtos afiliados (concluída)

- `Product` + `AffiliateLink` (migrations, models, services, policies,
  CRUD interno com listagem `ui.table`, detalhe e regra de link principal).
- Sidebar "Produtos" ativa; dashboard mostra contagem real de produtos.
- Campanhas e conteúdos seguem futuros (roadmap original mantido abaixo).

## Sprint 2 — Contas sociais + Account DNA (concluída)

- `SocialAccount` manual (Instagram/TikTok/YouTube) com DNA operacional,
  `SocialAccountPolicy` (regra de archived), CRUD com `ui.table` e detalhe
  em seções Conta + Account DNA.
- `config/locale-options.php` neutro (idiomas/mercados compartilhados).
- Sidebar Distribuição/Contas ativa; dashboard inalterado (sem poluição visual).
- OAuth, tokens, publicação e métricas seguem futuros.

## Sprint 3 — Personas e avatares (concluída)

- `Persona` (Communication DNA) + `Avatar` (Visual DNA, sem upload, só
  `reference_notes`), policies com regra de archived, CRUD com `ui.table`.
- `SocialAccount` com `default_persona_id`/`default_avatar_id` (belongsTo,
  selects só ativos/pausados, detalhe mostra defaults).
- Sidebar Creative Studio: Personas e Avatares ativos (no topo do grupo);
  dashboard inalterado.
- IA, geração, assets e OAuth seguem futuros.

## Sprint 4 — Referências (concluída)

- `ReferenceProfile` (reusa `SocialPlatform`) + `ReferenceContent` (tipos em
  `config/references.php`), policies com regra de archived, CRUD com `ui.table`,
  detalhe com seção de conteúdos (quick flow só-URL + detailed flow).
- Sem vínculo com Product (contexto via language/market/niche); sem IA,
  scraping, download ou embeddings. Geração assistida documentada como plano.
- Sidebar Inteligência/Referências ativa; Blueprints segue "Em breve".

## Sprint 5.0 — Fundação Gemini (concluída, teste real pendente de credencial)

- `AiTextProvider` + `GoogleGeminiTextProvider` (Interactions API, auth key,
  structured output), `ai_generations` sanitizado, tela admin Sistema → IA
  com teste real de conexão. Sem análise de negócio; testes com `Http::fake`.

## Validação real (pós-implementação, registrada)

- Endpoint/model/auth **confirmados** (HTTP 200 com interaction id).
- Structured output real **não validado**: `gemini-3.8-flash` e `gemini-3.7-flash`
  retornaram timeout/503 (`service_unavailable`, high demand). Sem evidência
  de erro de implementação; sem correção indicada.
- **Pendente: repetir teste real de structured output da Gemini Interactions
  API em janela sem service_unavailable/high demand** — verificar antes de
  considerar o primeiro fluxo real de análise de referências pronto.

## Próximas sprints (sugestão, sem compromisso)

- **Domínio base (restante):** campanhas e conteúdos (CRUD interno,
  validações, policies) — produtos concluídos na Sprint 1.
- **Sprint 2 — Creative Studio:** ideias, roteiros e referências/blueprints.
- **Sprint 3 — Mídia IA:** integração Gemini (imagens/vídeos) + controle de custos.
- **Sprint 4 — Distribuição:** contas sociais, calendário, publicação via APIs oficiais.
- **Sprint 5 — Performance:** métricas, conversões, padrões vencedores e variações.

## Explicitamente fora do escopo

Assinaturas, billing, planos, checkout, multi-tenancy comercial,
white-label, onboarding de clientes, marketplace.
