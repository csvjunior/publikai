# 04 — Pipeline de conteúdo

**Estado:** parcial (planejado + módulos 3/5.1/5.2/5.3) · **Atualizado em:** 2026-09-30

## Pipeline futuro previsto

1. Referências → 2. Blueprints/padrões → 3. Ideias → 4. Roteiros →
   5. Imagens/Vídeos → 6. Adaptação por rede → 7. Agendamento/Publicação →
   8. Métricas → 9. Variações de vencedores.

## Estado atual

- Apenas o **menu visual** do shell prevê essas etapas (itens marcados
  como “Em breve”, sem rotas nem controllers) — exceção: Personas e Avatares
  (Sprint 3), ativos no Creative Studio como identidades reutilizáveis.
- **Decisão:** não criar controllers/módulos vazios para preencher menu.
- Sprint 2 cadastrou o **Account DNA** das contas sociais, que orientará o
  futuro Content Engine (sem interpretação por IA ainda).
- Sprint 3 cadastrou **Communication DNA** (personas) e **Visual DNA**
  (avatares); contas referenciam defaults. Atores futuros (ideias, roteiros,
  campanhas) ainda não existem.
- Sprint 4 cadastrou a **base de referências** (perfis + conteúdos com
  observações manuais de padrões).
- Sprint 5.1 implementou **AI Reference Analysis** (histórico imutável,
  execução síncrona tolerante a falha).
- Sprint 5.2 implementou **propostas assistidas de Persona/Avatar**
  (`IdentityProposal` + revisão humana obrigatória + apply transacional).
  Fluxo atual: References → análise → proposta → revisão → apply.
- Sprint 5.3 cadastrou **Content Blueprints manuais** (estruturas reutilizáveis;
  roteiros seguem futuros).
- Sprint 5.4 implementou **Script Studio** (roteiros manuais + por IA, texto
  estruturado; vídeo/imagem/voz e publicação seguem futuros).
- Sprint 5.5.1 integrou **roteiros à Image Factory** (prompt montado do contexto,
  gallery e primary no detalhe; referência de imagem e edição seguem futuras).
- O preenchimento manual de Persona e Avatar continua disponível como
  fallback e edição final.

## Pendências

- Modelar entidades (produtos, campanhas, conteúdos, ideias, roteiros) na Sprint de domínio.
