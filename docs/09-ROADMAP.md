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

## Sprint 5.0 — Fundação Gemini (concluída; análise real em 5.1)

- `AiTextProvider` + `GoogleGeminiTextProvider` (Interactions API, auth key,
  structured output), `ai_generations` sanitizado, tela admin Sistema → IA
  com teste real de conexão. Testes com `Http::fake`.

## Sprint 5.1 — AI Reference Analysis (concluída)

- `ReferenceAnalysis` (histórico imutável) via `ReferenceAnalysisService`:
  só dados cadastrados, schema + instruções versionáveis, execução síncrona,
  falha sanitizada, retry como nova análise, `latestSuccessful` em destaque.
- Sem scraping, embeddings, Persona/Avatar gerados ou Blueprint.

## Sprint 5.2 — AI Persona & Avatar Proposals (concluída)

- `IdentityProposal` (ready/applied/discarded) via `IdentityProposalService`:
  IA propõe a partir da latest successful; humano revisa/edita; apply cria
  Persona + Avatar **active** em transação idempotente.
- Sem imagem, voz, roteiro, Blueprint ou Campaign.

## Sprint 5.3 — Content Blueprints (concluída)

- `ContentBlueprint` manual (slug único, `source_type` automático,
  taxonomia `content_types` compartilhada): ESTRUTURAS reutilizáveis, sem
  roteiro/mídia e sem vínculo Product/Persona/Avatar.
- Sidebar Inteligência/Blueprints ativa; `ai_assisted` preparado p/ futuro.

## Sprint 5.4 — Script Studio (concluída)

- `ContentScript` manual (draft→ready→approved) + por IA (generating→ready|failed):
  texto estruturado de Product + Blueprint + Persona + Avatar, com revisão e
  aprovação humanas. CTA textual, sem links/mídia/publicação/Campaign.
- Sidebar Creative Studio/Roteiros ativa.

## Sprint 5.5.0 — Image Factory foundation (concluída, teste real pendente)

- `AiImageProvider` + `GoogleGeminiImageProvider` (Nano Banana 2,
  `gemini-3.1-flash-image`, 1K/9:16, sem retry), `MediaAsset` genérico,
  Storage público, tela admin com preview + últimas 10. Sem Script/Avatar
  reference, edição, vídeo ou Campaign.

## Sprint 5.5.1 — Image Factory contextual (concluída)

- Roteiro ready/approved → `VisualPromptBuilder` → prompt revisável →
  request com contexto → Job reaproveitado vincula asset (primary
  transacional) → gallery + "Definir como principal" no detalhe.
- Sem reference image, edição, vídeo, Campaign ou Media Library completa.

## Sprint 5.5.2 — Avatar reference image foundation (concluída, QA real success)

- Avatar com uma referência ativa (`MediaAsset` uploaded): upload/preview/
  substituir/remover; geração contextual envia text+image ao provider quando
  há referência (snapshot no request), fallback textual sem ela.
- Sem edição, vídeo, product/reference image, biometria ou garantia de identidade.

## Sprint 5.5.3 — Multi-reference Avatar consistency (concluída, QA real success)

- Até 4 referências por Avatar (primary + auxiliares, seleção humana,
  Visual DNA only); snapshot múltiplo; provider text+N images; consistência
  visual do personagem validada em QA real.

## Sprint 5.5.4 — Controlled image editing (concluída, QA real success)

- "Criar variação" por asset: source snapshot + mudança desejada (+ refs
  opcionais) → `image_edit` → novo asset derivado; consistência visual
  validada em QA real.

## Sprint 5.6.0 — Video Factory foundation (concluída, QA real success)

- Omni image-to-video: técnica admin + contextual por Script; MP4 720×1280
  validado em QA real com FFprobe; playback na gallery.

## Sprint 5.6.1 — Video composition with FFmpeg (concluída)

- Composição local (trim + imagem + concat 720p30 sem áudio);
  request + inputs com snapshot; composer UI; badge "Composição".

## Sprint 5.6.2 — Voice/TTS + audio pipeline (implementada, QA real pendente)

- Narração TTS (voz oficial + texto do roteiro → WAV);
  QA real posterior (responsável, UMA narração).

## Microcorreção async 5.5.0 (geração longa fora do request)

- Parser REST corrigido (`steps→model_output→content`; decisão 31).
- Geração **assíncrona**: `ImageGenerationRequest` + `GenerateImageJob`
  (queue `database`, `tries=1`, timeout 90s, HTTP 60s, idempotente); POST
  retorna rápido; UI mostra gerações; worker local `queue:work`.

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
