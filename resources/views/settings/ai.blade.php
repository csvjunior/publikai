@extends('layouts.app')

@section('title', 'IA')
@section('header', 'IA')
@section('content')
<x-ui.page-header
    title="IA"
    description="Provider de inteligência artificial e validação de conexão."
    :breadcrumbs="[['label' => 'Sistema'], ['label' => 'IA']]"
/>

@if (session('ai_test'))
    @if (session('ai_test')['ok'])
        <x-ui.alert variant="success" title="Conexão validada" class="mb-6">
            Status: {{ session('ai_test')['status'] }} · {{ session('ai_test')['message'] }}
            @if (! empty(session('ai_test')['duration_ms']))
                <span class="t-small">({{ session('ai_test')['duration_ms'] }} ms)</span>
            @endif
        </x-ui.alert>
    @else
        <x-ui.alert variant="danger" title="Falha na conexão" class="mb-6">
            {{ session('ai_test')['message'] }}
            <span class="t-small">Código: {{ session('ai_test')['error_code'] }}</span>
        </x-ui.alert>
    @endif
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

        @if (! $configured)
            <p class="t-small mt-4">Defina <code>GOOGLE_AI_ENABLED=true</code> e <code>GOOGLE_AI_AUTH_KEY</code> no <code>.env</code> para habilitar. A chave nunca é exibida aqui.</p>
        @endif
    </x-ui.card>

    <x-ui.card title="Teste de conexão" description="Chamada real mínima com structured output (limitada por rate limit).">
        <form method="POST" action="{{ route('settings.ai.test') }}">
            @csrf
            @if ($configured)
                <x-ui.button variant="primary" type="submit">Testar conexão</x-ui.button>
            @else
                <x-ui.button variant="primary" type="submit" disabled>Testar conexão</x-ui.button>
            @endif
        </form>
    </x-ui.card>
</div>
@endsection
