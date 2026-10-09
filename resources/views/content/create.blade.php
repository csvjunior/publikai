@extends('layouts.app')

@section('title', 'Criar conteúdo')
@section('header', 'Conteúdo')
@section('content')
<x-ui.page-header
    title="Criar conteúdo"
    description="O que você quer criar?"
    :breadcrumbs="[['label' => 'Conteúdo', 'url' => route('content.index')], ['label' => 'Criar']]"
/>

@if (! request()->query('type'))
    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.card title="Vídeo" description="Roteiro, clipe e narração para Instagram e TikTok.">
            <x-ui.button :href="route('content.create', ['type' => 'video'])" variant="ai">Criar vídeo</x-ui.button>
        </x-ui.card>
        <x-ui.card title="Imagem" description="Imagem a partir do produto e da identidade.">
            <x-ui.button :href="route('content.create', ['type' => 'image'])" variant="outline">Criar imagem</x-ui.button>
        </x-ui.card>
    </div>
@else
    <div class="space-y-6">
        <x-ui.card title="{{ $type->label() }}" description="Produto + Persona + Avatar + objetivo. O resto o Publikai resolve.">
            <form method="POST" action="{{ route('content.store') }}" data-once novalidate>
                @csrf
                <input type="hidden" name="type" value="{{ $type->value }}">
                <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-ui.select label="Produto" name="product_id" :options="$productOptions" :value="old('product_id')" placeholder="Selecionar produto" required />
                        <x-ui.select label="Persona" name="persona_id" :options="$personaOptions" :value="old('persona_id')" placeholder="Selecionar persona" helper="Define como o conteúdo se comunica." required />
                        <x-ui.select label="Avatar" name="avatar_id" :options="$avatarOptions" :value="old('avatar_id')" placeholder="Selecionar avatar" helper="Define quem aparece visualmente." required />
                    </div>
                    <x-ui.textarea label="Objetivo do conteúdo" name="objective" :rows="3" required helper="Descreva o que você quer comunicar ou vender." :value="old('objective')" />
                    <x-ui.textarea label="Orientação (opcional)" name="guidance" :rows="2" helper="Ex.: uma cena em cozinha moderna, estilo UGC." :value="old('guidance')" />
                    <details class="rounded-lg border border-border p-3">
                        <summary class="cursor-pointer text-sm font-medium text-ink-secondary">Configurações avançadas</summary>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <x-ui.select label="Roteiro base" name="content_blueprint_id" :options="['' => 'Automático'.($defaultBlueprint ? ' ('.$defaultBlueprint->name.')' : '')] + $blueprintOptions" :value="old('content_blueprint_id')" />
                        </div>
                    </details>
                    <div>
                        <x-ui.button variant="ai" type="submit">Gerar roteiro</x-ui.button>
                        <p class="t-small mt-2">Revise o roteiro antes de produzir para evitar gerações desnecessárias.</p>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>
@endif
@endsection
