@extends('layouts.app')

@section('title', 'Criar narração')
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    :title="'Criar narração: '.$script->title"
    description="Transforme o roteiro em voz. Revise o texto antes de gerar."
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => $script->title, 'url' => route('scripts.show', $script)], ['label' => 'Criar narração']]"
/>

@if (session('audio_notice'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('audio_notice') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Contexto" description="Resumo read-only do roteiro.">
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Roteiro</dt>
                <dd class="t-body mt-0.5">{{ $script->title }} · {{ $script->status->label() }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Persona</dt>
                <dd class="t-body mt-0.5">{{ $script->persona->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Idioma</dt>
                <dd class="t-body mt-0.5">{{ $script->language ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Vozes</dt>
                <dd class="t-body mt-0.5">Somente vozes sintéticas oficiais do provider.</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Narração" description="Revise o texto antes de gerar a voz — a edição não altera o roteiro.">
        <form method="POST" action="{{ route('scripts.audio.store', $script) }}" data-once novalidate>
            @csrf
            <div class="space-y-4">
                <x-ui.textarea label="Texto" name="text" :rows="8" required helper="Somente o texto é narrado; estilo de entrega vem do tom da Persona." :value="old('text', $text)" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select label="Voz" name="voice" :options="$voices" :value="old('voice', $defaultVoice)" />
                </div>
                @if ($aiConfigured)
                    <x-ui.button variant="ai" type="submit">Gerar narração</x-ui.button>
                    <p class="t-small mt-2">A geração continuará em segundo plano.</p>
                @else
                    <x-ui.button variant="ai" type="button" disabled>Gerar narração</x-ui.button>
                    <p class="t-small mt-2">Configure a geração de narrações em Sistema → IA.</p>
                @endif
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
