@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('content')
<x-ui.page-header
    title="Dashboard"
    description="Crie e acompanhe conteúdos para Instagram e TikTok em um só lugar."
/>

<div class="space-y-6">
    <x-ui.card title="Criar conteúdo" description="Vídeo ou imagem para Instagram e TikTok em poucos passos.">
        <div class="flex flex-wrap gap-2">
            <x-ui.button :href="route('content.create', ['type' => 'video'])" variant="ai">Criar vídeo</x-ui.button>
            <x-ui.button :href="route('content.create', ['type' => 'image'])" variant="outline">Criar imagem</x-ui.button>
        </div>
    </x-ui.card>

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

        <x-ui.stat-card title="Conteúdos" value="—" hint="Vídeos e imagens criados e acompanhados no Publikai.">
            <a href="{{ route('content.index') }}" class="text-sm font-medium text-primary hover:text-primary-hover">Ver conteúdos</a>
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
