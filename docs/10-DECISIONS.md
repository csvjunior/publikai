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
