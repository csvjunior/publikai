@extends('layouts.app')

@section('title', $script->title)
@section('header', 'Conteúdo')
@section('content')
<x-ui.page-header
    :title="$type->label().' · '.($script->product?->name ?? $script->title)"
    :description="$script->persona?->name.' · '.$script->avatar?->name"
    :breadcrumbs="[['label' => 'Conteúdo', 'url' => route('content.index')], ['label' => $script->title]]"
>
    <x-slot:actions>
        <x-ui.badge variant="neutral">{{ $statusLabel }}</x-ui.badge>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

<div class="space-y-6">
    @if (in_array($statusLabel, ['Rascunho', 'Roteiro para revisar', 'Pronto para produzir'], true))
        <x-ui.card title="Roteiro pronto para revisão" description="Revise o texto antes de produzir para evitar gerações desnecessárias.">
            <div class="space-y-3">
                @if ($script->hook)
                    <p class="t-body whitespace-pre-line">{{ $script->hook }}</p>
                @endif
                @if ($script->body)
                    <p class="t-body whitespace-pre-line">{{ $script->body }}</p>
                @endif
                @if ($script->cta)
                    <p class="t-body whitespace-pre-line">{{ $script->cta }}</p>
                @endif
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                @if (! $production)
                    <form method="POST" action="{{ route('content.produce', ['content' => $script]) }}">
                        @csrf
                        <x-ui.button variant="ai" type="submit">Produzir vídeo</x-ui.button>
                    </form>
                @endif
                @if ($script->isEditable())
                    <x-ui.button :href="route('scripts.edit', $script)" variant="outline">Editar roteiro</x-ui.button>
                @endif
            </div>
        </x-ui.card>
    @endif

    @if ($production && $production->isActive())
        <x-ui.card title="Produção do vídeo" description="Uma ação sua, resto com a gente.">
            <ol class="space-y-2">
                @foreach ($production->stepStates() as $step)
                    <li class="flex items-center gap-2 text-sm">
                        @if ($step['state'] === 'done')
                            <x-ui.badge variant="success">✓</x-ui.badge>
                        @elseif ($step['state'] === 'current')
                            <x-ui.badge variant="info">●</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">○</x-ui.badge>
                        @endif
                        <span class="{{ $step['state'] === 'current' ? 'font-medium text-ink' : 'text-ink-secondary' }}">{{ $step['label'] }}</span>
                    </li>
                @endforeach
            </ol>
            @if ($production->isWaiting())
                <p class="t-body mt-3">Aguardando processamento. Atualize a página para acompanhar.</p>
            @else
                <p class="t-body mt-3">Produzindo vídeo… Atualize a página para acompanhar.</p>
            @endif
        </x-ui.card>
    @endif

    @if ($production && $production->isFailed())
        <x-ui.card title="Produção do vídeo" description="Uma ação sua, resto com a gente.">
            <ol class="space-y-2">
                @foreach ($production->stepStates() as $step)
                    <li class="flex items-center gap-2 text-sm">
                        @if ($step['state'] === 'done')
                            <x-ui.badge variant="success">✓</x-ui.badge>
                        @elseif ($step['state'] === 'failed')
                            <x-ui.badge variant="danger">!</x-ui.badge>
                        @elseif ($step['state'] === 'current')
                            <x-ui.badge variant="info">●</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">○</x-ui.badge>
                        @endif
                        <span class="{{ in_array($step['state'], ['current', 'failed'], true) ? 'font-medium text-ink' : 'text-ink-secondary' }}">{{ $step['label'] }}</span>
                    </li>
                @endforeach
            </ol>
            <p class="t-body mt-3">Não foi possível concluir o vídeo.</p>
            @if ($production->failureHint())
                <p class="t-small mt-1">{{ $production->failureHint() }}</p>
            @endif
            <form method="POST" action="{{ route('content.produce', ['content' => $script]) }}" class="mt-3">
                @csrf
                <x-ui.button variant="ai" type="submit">Tentar novamente</x-ui.button>
            </form>
            <p class="t-small mt-2">Continuaremos a partir da etapa necessária.</p>
        </x-ui.card>
    @endif

    @if ($production && $production->isSuccess())
        <x-ui.card title="Vídeo pronto" description="Seu vídeo final está abaixo.">
            @if ($flow['final']['asset']?->url())
                <video src="{{ $flow['final']['asset']->url() }}" controls preload="metadata" class="w-full bg-black {{ $flow['final']['asset']->orientationClass() }}"></video>
                <div class="mt-2 flex flex-wrap items-center gap-1">
                    <x-ui.badge variant="ai">Final</x-ui.badge>
                    @if ($flow['final']['asset']->duration_seconds)
                        <span class="t-small">{{ $flow['final']['asset']->duration_seconds }}s · 9:16</span>
                    @endif
                </div>
                <div class="mt-2">
                    <a href="{{ $flow['final']['asset']->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir vídeo</a>
                </div>
            @endif
            <ol class="mt-4 space-y-2">
                @foreach (['Preparando', 'Preparando visual', 'Criando vídeo', 'Criando narração', 'Finalizando vídeo'] as $label)
                    <li class="flex items-center gap-2 text-sm">
                        <x-ui.badge variant="success">✓</x-ui.badge>
                        <span class="text-ink-secondary">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
            <form method="POST" action="{{ route('content.produce', ['content' => $script]) }}" class="mt-3">
                @csrf
                <input type="hidden" name="force_new" value="1">
                <button type="submit" class="text-xs font-medium text-primary hover:text-primary-hover">Criar nova versão</button>
            </form>
            <p class="t-small mt-1">Cria uma nova versão reutilizando o que for possível.</p>
        </x-ui.card>
    @endif

    <x-ui.card title="Detalhes">
        <details class="rounded-lg border border-border p-3">
            <summary class="cursor-pointer text-sm font-medium text-ink-secondary">Roteiro e materiais</summary>
            <div class="mt-3 space-y-2">
                @if ($script->hook)
                    <p class="t-body whitespace-pre-line">{{ $script->hook }}</p>
                @endif
                @if ($script->body)
                    <p class="t-body whitespace-pre-line">{{ $script->body }}</p>
                @endif
                @if ($script->cta)
                    <p class="t-body whitespace-pre-line">{{ $script->cta }}</p>
                @endif
                <a href="{{ route('scripts.show', $script) }}" class="inline-block text-xs font-medium text-primary hover:text-primary-hover">Ver detalhes técnicos</a>
            </div>
        </details>
    </x-ui.card>
</div>
@endsection
