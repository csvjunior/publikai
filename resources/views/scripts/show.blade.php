@extends('layouts.app')

@section('title', $script->title)
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    :title="$script->title"
    :description="$script->objective"
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => $script->title]]"
>
    <x-slot:actions>
        @if ($script->isEditable())
            <x-ui.button :href="route('scripts.edit', $script)" variant="outline">Editar</x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if (session('script_notice'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('script_notice') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Contexto">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :variant="$script->status->badgeVariant()">{{ $script->status->label() }}</x-ui.badge>
            @if ($script->generation_source === \App\Enums\ContentScriptSource::Ai)
                <x-ui.badge variant="ai">IA</x-ui.badge>
            @else
                <x-ui.badge variant="neutral">Manual</x-ui.badge>
            @endif
            @if ($script->language)
                <x-ui.badge variant="neutral">{{ $script->language }}</x-ui.badge>
            @endif
            @if ($script->market)
                <x-ui.badge variant="info">{{ $script->market }}</x-ui.badge>
            @endif
        </div>

        <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Produto</dt>
                <dd class="t-body mt-0.5">
                    @if ($script->product)
                        <a href="{{ route('products.show', $script->product) }}" class="font-medium text-primary hover:text-primary-hover">{{ $script->product->name }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Blueprint</dt>
                <dd class="t-body mt-0.5">
                    @if ($script->blueprint)
                        <a href="{{ route('blueprints.show', $script->blueprint) }}" class="font-medium text-primary hover:text-primary-hover">{{ $script->blueprint->name }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Persona</dt>
                <dd class="t-body mt-0.5">
                    @if ($script->persona)
                        <a href="{{ route('personas.show', $script->persona) }}" class="font-medium text-primary hover:text-primary-hover">{{ $script->persona->name }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Avatar</dt>
                <dd class="t-body mt-0.5">
                    @if ($script->avatar)
                        <a href="{{ route('avatars.show', $script->avatar) }}" class="font-medium text-primary hover:text-primary-hover">{{ $script->avatar->name }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>

        <div class="mt-4 flex flex-col gap-2 border-t border-border pt-4 sm:flex-row">
            @if ($script->status === \App\Enums\ContentScriptStatus::Draft)
                <form method="POST" action="{{ route('scripts.ready', $script) }}" class="sm:w-auto">
                    @csrf
                    <x-ui.button variant="secondary" type="submit" full>Marcar como pronto</x-ui.button>
                </form>
            @endif
            @if ($script->isReady())
                <form method="POST" action="{{ route('scripts.approve', $script) }}" class="sm:w-auto">
                    @csrf
                    <x-ui.button variant="primary" type="submit" full>Aprovar roteiro</x-ui.button>
                </form>
            @endif
            @if ($script->isFailed())
                <x-ui.button :href="route('scripts.create')" variant="outline">Tentar novamente (novo roteiro)</x-ui.button>
            @endif
        </div>
    </x-ui.card>

    @if ($script->isFailed())
        <x-ui.card title="Falha na geração">
            <p class="t-card-title">Não foi possível gerar o roteiro</p>
            <p class="t-body mt-1">Tente novamente mais tarde criando um novo roteiro.</p>
        </x-ui.card>
    @else
        <x-ui.card title="Roteiro">
            <div class="space-y-4">
                <div>
                    <h4 class="t-section-title">Hook</h4>
                    <p class="t-body mt-1 whitespace-pre-line">{{ $script->hook }}</p>
                </div>
                @if ($script->opening)
                    <div>
                        <h4 class="t-section-title">Abertura</h4>
                        <p class="t-body mt-1 whitespace-pre-line">{{ $script->opening }}</p>
                    </div>
                @endif
                <div>
                    <h4 class="t-section-title">Corpo</h4>
                    <p class="t-body mt-1 whitespace-pre-line">{{ $script->body }}</p>
                </div>
                <div>
                    <h4 class="t-section-title">CTA</h4>
                    <p class="t-body mt-1 whitespace-pre-line">{{ $script->cta }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Produção">
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Texto na tela</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $script->on_screen_text ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Direção visual</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $script->visual_direction ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Direção de voz</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $script->voice_direction ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Duração</dt>
                    <dd class="t-body mt-0.5">{{ $script->duration_seconds ? $script->duration_seconds.'s' : '—' }}</dd>
                </div>
                @if ($script->generation_source === \App\Enums\ContentScriptSource::Ai)
                    <div>
                        <dt class="t-small font-medium uppercase tracking-wide">Origem IA</dt>
                        <dd class="t-body mt-0.5">{{ $script->provider ?? '—' }} / {{ $script->model ?? '—' }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>
    @endif
</div>
@endsection
