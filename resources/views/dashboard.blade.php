@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('content')
<x-ui.page-header
    title="Dashboard"
    description="Visão geral da operação. O sistema está em fase inicial: a fundação visual e a autenticação estão prontas; os módulos de operação chegam nas próximas sprints."
/>

<div class="space-y-6">
    <x-ui.card
        title="Olá, {{ auth()->user()->name }}!"
        description="Publikai · ferramenta interna da Jaguartec. Seu perfil: {{ auth()->user()->role->value }}."
    >
        <x-ui.empty-state
            title="Nenhuma atividade ainda"
            description="Quando houver produtos, conteúdos e publicações, o resumo da operação aparece aqui."
        />
    </x-ui.card>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card title="Produtos" :value="$productsCount" hint="Produtos cadastrados para as operações.">
            <a href="{{ route('products.index') }}" class="text-sm font-medium text-primary hover:text-primary-hover">Ver produtos</a>
        </x-ui.stat-card>

        <x-ui.stat-card title="Conteúdos" value="—" hint="Roteiros, ideias e peças ainda não existem.">
            <x-ui.badge variant="neutral">Ainda sem dados</x-ui.badge>
        </x-ui.stat-card>

        <x-ui.stat-card title="Publicações" value="—" hint="Nenhum agendamento realizado até o momento.">
            <x-ui.badge variant="neutral">Ainda sem dados</x-ui.badge>
        </x-ui.stat-card>

        <x-ui.stat-card title="Métricas" value="—" hint="Sem dados de desempenho coletados.">
            <x-ui.badge variant="neutral">Ainda sem dados</x-ui.badge>
        </x-ui.stat-card>
    </div>
</div>
@endsection
