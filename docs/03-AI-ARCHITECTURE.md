# 03 — Arquitetura de IA

**Estado:** planejado (nada implementado) · **Atualizado em:** 2026-09-29

## Decisão atual

- **Nenhum provider de IA integrado** nesta Sprint.
- **Planejado:** Google/Gemini como primeiro provider de geração
  (texto/imagem/vídeo), ainda sem credenciais, sem SDK e sem chamadas.

## Diretrizes futuras (quando a operação justificar)

- Isolar provedores atrás de interfaces/serviços próprios (evitar acoplamento).
- Registrar custo por chamada para o futuro módulo de custos.
- Nunca colocar API keys no código; usar `.env` + `.env.example` (nomes vazios).
- Não registrar prompts sensíveis, tokens ou segredos em logs.

## Estado após a Sprint 3 (continua sem IA)

- Personas (Communication DNA) e avatares (Visual DNA) existem como **dados
  cadastrais organizados** para consumo futuro do Content Engine.
- Nenhuma chamada a Gemini/OpenAI/Nano Banana/Veo/TTS; nenhuma geração de
  texto, imagem, vídeo ou voz; nenhum prompt automático.

## Pendências

- Definir variáveis de ambiente do provider escolhido (somente quando integrar).
- Definir estratégia de fila para gerações demoradas (nativa Laravel).
