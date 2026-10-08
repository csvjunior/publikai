@extends('layouts.app')

@section('title', 'IA — Narrações')
@section('header', 'IA')
@section('content')
<x-ui.page-header
    title="Narrações de teste"
    description="Validação do provider de texto-para-voz (Gemini TTS)."
    :breadcrumbs="[['label' => 'Sistema'], ['label' => 'IA', 'url' => route('settings.ai')], ['label' => 'Narrações']]"
/>

@if (session('audio_test') && ! session('audio_test')['ok'])
    <x-ui.alert variant="danger" title="Falha na geração" class="mb-6">
        {{ session('audio_test')['message'] }}
        <span class="t-small">Código: {{ session('audio_test')['error_code'] }}</span>
    </x-ui.alert>
@endif

@if (session('audio_test') && (session('audio_test')['started'] ?? false))
    <x-ui.alert variant="info" title="Geração iniciada" class="mb-6">
        {{ session('audio_test')['message'] }}
    </x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Provider">
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Provider</dt>
                <dd class="t-body mt-0.5 font-medium text-ink">{{ $provider }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Modelo</dt>
                <dd class="t-body mt-0.5 font-medium text-ink">{{ $model }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Voz padrão</dt>
                <dd class="t-body mt-0.5">{{ $defaultVoice }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Configuração</dt>
                <dd class="mt-0.5">
                    @if ($configured)
                        <x-ui.badge variant="success">Configurado</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning">Não configurado</x-ui.badge>
                    @endif
                </dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Gerar narração de teste" description="Texto-para-voz com voz sintética oficial.">
        <form method="POST" action="{{ route('settings.ai.audio.store') }}" novalidate>
            @csrf
            <div class="space-y-4">
                <x-ui.textarea label="Texto" name="text" :rows="4" required helper="Texto curto e neutro para teste." :value="old('text')" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select label="Voz" name="voice" :options="$voices" :value="old('voice', $defaultVoice)" />
                </div>
                @if ($configured)
                    <x-ui.button variant="primary" type="submit">Gerar narração de teste</x-ui.button>
                @else
                    <x-ui.button variant="primary" type="button" disabled>Gerar narração de teste</x-ui.button>
                    <p class="t-small mt-2">Defina <code>GOOGLE_AI_AUDIO_ENABLED=true</code> no <code>.env</code> (usa a mesma Auth Key) para habilitar.</p>
                @endif
            </div>
        </form>
    </x-ui.card>

    @if ($recent->isNotEmpty())
        <x-ui.card title="Requisições recentes">
            <p class="t-small mb-3">Acompanhe o processamento. Atualize a página para ver o resultado (worker precisa estar rodando).</p>
            <ul class="space-y-2">
                @foreach ($recent as $generation)
                    <li class="flex flex-wrap items-center gap-2 text-sm">
                        <x-ui.badge :variant="$generation->status->value === 'failed' ? 'danger' : ($generation->status->value === 'success' ? 'success' : 'info')">{{ $generation->status->label() }}</x-ui.badge>
                        <span class="t-small font-medium">{{ $generation->voice ?? '—' }}</span>
                        <span class="min-w-0 flex-1 truncate text-ink-secondary">{{ \Illuminate\Support\Str::limit($generation->text, 80) }}</span>
                        <span class="text-ink-muted">{{ $generation->created_at?->display() }}</span>
                        @if ($generation->status->value === 'failed' && $generation->error_code)
                            <span class="text-ink-muted">({{ $generation->error_code }})</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
</div>
@endsection
