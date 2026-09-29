# 10 — Decisões (ADR simplificado)

**Atualizado em:** 2026-09-29

| # | Data | Decisão | Motivo | Status |
|---|---|---|---|---|
| 01 | 2026-09-29 | Autenticação manual nativa, sem Breeze/Jetstream/Fortify/pacotes externos | Requisito da Sprint; fluxo simples; evitar dependência | Atual |
| 02 | 2026-09-29 | `REGISTRATION_ENABLED` + `REGISTRATION_CODE` (comparação servidor, `hash_equals`) | Cadastro interno controlável sem expor segredo | Atual |
| 03 | 2026-09-29 | Primeiro usuário `admin`, demais `operator` | Suporte mínimo a 2 funções sem sistema complexo | Atual |
| 04 | 2026-09-29 | `users.role` como `string(20)` + `App\Enums\UserRole` | Compatibilidade MariaDB 10.4 ↔ MySQL 8; evolução simples | Atual |
| 05 | 2026-09-29 | Menu completo visível com itens “Em breve”, sem controllers vazios | Evitar abstrações prematuras; comunicar roadmap | Atual |
| 06 | 2026-09-29 | Dashboard sem métricas reais; placeholders “Ainda sem dados” | Não fabricar dados | Atual |
| 07 | 2026-09-29 | Sem Laravel Boost, sem novas ferramentas de qualidade | Regra: não instalar sem justificar | Atual |
| 08 | 2026-09-29 | Google/Gemini planejado como 1º provider de IA, sem integração | Operação ainda não justifica | Planejado |
| 09 | 2026-09-29 | `.env.example` com `REGISTRATION_*` vazios; segredos só no `.env` | Segurança | Atual |
| 10 | 2026-09-29 | Repositório Git local inicializado; primeiro commit da Sprint 0.1 criado (sem remote, sem tag) | Fechamento técnico da Sprint | Atual |
| 11 | 2026-09-29 | `.env.example` alinhado com MySQL/MariaDB (`publikai_db`); FFmpeg 9.0.2 / FFprobe 9.0.2 validado no Windows | Encerramento: sem senha real, sem reinstalação | Atual |
| 12 | 2026-09-29 | Contrato permanente de Design System/UX/responsividade (`docs/11-DESIGN-SYSTEM.md`); implementação inicial a cargo da Sprint 0.2, telas 0.1 marcadas para migração | Complemento permanente ao contrato do projeto | Atual |
| 13 | 2026-09-29 | Design System implementado (Sprint 0.2): `design-system.css` via `@import` no pipeline Tailwind v4 (sem nova estrutura); `text-*` do contrato mapeados para `text-ink*` (evita colisão com `text-primary` do acento); `modal`/`tabs`/`table` diferidos sem caso de uso | Evitar over-engineering; documentado em `docs/11` e `SPRINT-00.2` | Atual |
| 14 | 2026-09-29 | Formulários com `novalidate` (validação 100% servidor com erros acessíveis); fonte externa não instalada (stack do skeleton servida no build) | UX consistente + regra de não instalar dependência por estética | Atual |
| 15 | 2026-09-29 | Revisão visual humana da Sprint 0.2 **aprovada**: cadastro, dashboard desktop/mobile, sidebar, drawer mobile, cards, tipografia, gutters, layout global, sem overflow horizontal, consistência geral | Fechamento técnico da Sprint 0.2 | Atual |
| 16 | 2026-09-29 | Produtos afiliados (Sprint 1): `products` + `affiliate_links` com `ProductStatus` (string, sem ENUM nativo); slug único via `ProductService`; link principal único via `AffiliateLinkService` (transação); idiomas/mercados/moedas como códigos + `config/products.php` (sem tabelas i18n); arquivamento = status, sem delete físico | Primeira Sprint de domínio; simplicidade antes de infraestrutura | Atual |
| 17 | 2026-09-29 | Arquivamento via formulário de edição (sem rota dedicada); erros de validação de links usam error bag padrão (podem aparecer em mais de um formulário inline da mesma página) | Escopo mínimo;UX aceitável p/ ferramenta interna; reavaliar se confundir | Atual |
| 18 | 2026-09-29 | `x-ui.table` implementado (primeiro caso real: listagem de produtos) + `.pk-table` no DS; dashboard com contagem real de produtos (acoplamento mínimo intencional) | Componente só criado com uso real; sem métricas fabricadas | Atual |
| 19 | 2026-09-29 | Qualquer transição para ou a partir de `ProductStatus::Archived` exige papel admin; operators alternam apenas entre active e paused (regra centralizada em `ProductPolicy@updateStatus`; interface trava o status p/ operator em produto arquivado) | Correção de assimetria pré-commit da Sprint 1 | Atual |
| 20 | 2026-09-29 | Contas sociais (Sprint 2): `config/locale-options.php` neutro p/ idiomas/mercados (refactor pequeno de `config/products.php`); `unique(platform, username)` (mesmo nome em redes diferentes OK); sem service (CRUD simples, regra só na policy); dashboard inalterado | Evitar duplicação real sem over-engineering | Atual |
| 21 | 2026-09-29 | Personas e avatares (Sprint 3): só cadastro/organização, sem IA/geração/upload; `ethnicity_description` manual sem inferência; `reference_notes` como instrução futura; FKs `nullOnDelete`; selects de identidade só ativos/pausados (arquivados seguem referenciáveis); Personas/Avatares no topo do Creative Studio; dashboard inalterado | Identidades reutilizáveis antes do Content Engine | Atual |
| 22 | 2026-09-29 | Revisão visual humana da Sprint 3 **aprovada**: personas, avatares, Communication/Visual DNA, selects e detalhe da conta, sidebar | Fechamento técnico da Sprint 3 | Atual |
