# 00 — Visão do Publikai

**Estado:** atual (Sprint 0.1) · **Atualizado em:** 2026-09-29

## O que é

O Publikai é uma **plataforma interna da Jaguartec Tecnologia** para automatização e
gerenciamento de operações de conteúdo voltadas a **marketing de afiliados**.

## O que não é

- **Não é um SaaS comercial** neste momento.
- Não possui assinaturas, billing, planos, checkout, multi-tenancy comercial,
  white-label, onboarding de clientes ou marketplace.
- Uso **exclusivo da equipe interna** da Jaguartec.

## Futuro planejado (não implementado)

- Cadastro de produtos e links de afiliado.
- Operação em diferentes mercados e idiomas.
- Gestão de múltiplas contas (Instagram, TikTok, YouTube).
- Personas e avatares consistentes.
- Perfis e conteúdos de referência; análise de padrões sem cópia.
- Geração de estratégias, hooks e roteiros.
- Geração de imagens e vídeos via provedores de IA.
- Adaptação de conteúdo por rede social.
- Publicação e agendamento via APIs oficiais.
- Coleta de métricas e detecção de padrões vencedores.
- Variações de conteúdos vencedores.
- Monitoramento de custos de APIs de IA.

## Estado atual (Sprint 0.1)

- Fundação técnica: autenticação interna (cadastro, login, logout,
  recuperação de senha), shell administrativo responsivo e dashboard inicial.
- Banco MariaDB local `publikai_db` com migrations oficiais + campo `role`.
- Documentação base em `docs/`.

## Decisão

Começar simples e crescer somente quando a operação real justificar.
Prioridade: simplicidade, segurança, clareza e baixo custo operacional.
