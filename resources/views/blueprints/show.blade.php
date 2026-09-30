@extends('layouts.app')

@section('title', $blueprint->name)
@section('header', 'Blueprints')
@section('content')
<x-ui.page-header
    :title="$blueprint->name"
    :description="$blueprint->description"
    :breadcrumbs="[['label' => 'Blueprints', 'url' => route('blueprints.index')], ['label' => $blueprint->name]]"
>
    <x-slot:actions>
        <x-ui.button :href="route('blueprints.edit', $blueprint)" variant="outline">Editar</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Estratégia">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :variant="$blueprint->status->badgeVariant()">{{ $blueprint->status->label() }}</x-ui.badge>
            @if ($blueprint->content_type)
                <x-ui.badge variant="info">{{ config('references.content_types')[$blueprint->content_type] ?? $blueprint->content_type }}</x-ui.badge>
            @endif
            @if ($blueprint->source_type === \App\Enums\ContentBlueprintSourceType::AiAssisted)
                <x-ui.badge variant="ai">Assistido por IA</x-ui.badge>
            @else
                <x-ui.badge variant="neutral">Manual</x-ui.badge>
            @endif
        </div>

        <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Objetivo</dt>
                <dd class="t-body mt-0.5">{{ $blueprint->objective ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Hook</dt>
                <dd class="t-body mt-0.5">{{ $blueprint->hook_pattern ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Estrutura</dt>
                <dd class="t-body mt-0.5 whitespace-pre-line">{{ $blueprint->structure_pattern ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">CTA</dt>
                <dd class="t-body mt-0.5">{{ $blueprint->cta_pattern ?? '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Estilo">
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Visual</dt>
                <dd class="t-body mt-0.5">{{ $blueprint->visual_style ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Comunicação</dt>
                <dd class="t-body mt-0.5">{{ $blueprint->communication_style ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Duração recomendada</dt>
                <dd class="t-body mt-0.5">{{ $blueprint->recommended_duration_seconds ? $blueprint->recommended_duration_seconds.'s' : '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Contexto">
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Idioma / Mercado</dt>
                <dd class="t-body mt-0.5">{{ $blueprint->language ?? '—' }} · {{ $blueprint->market ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Nicho</dt>
                <dd class="t-body mt-0.5">{{ $blueprint->niche ?? '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Origem">
        @if ($blueprint->source_type === \App\Enums\ContentBlueprintSourceType::AiAssisted)
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Perfil de referência</dt>
                    <dd class="t-body mt-0.5">
                        @if ($blueprint->sourceProfile)
                            <a href="{{ route('references.show', $blueprint->sourceProfile) }}" class="font-medium text-primary hover:text-primary-hover">{{ $blueprint->sourceProfile->name }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Análise de origem</dt>
                    <dd class="t-body mt-0.5">{{ $blueprint->source_reference_analysis_id ? '#'.$blueprint->source_reference_analysis_id : '—' }}</dd>
                </div>
            </dl>
        @else
            <p class="t-body">Criado manualmente pela equipe.</p>
        @endif

        @if ($blueprint->notes)
            <p class="t-body mt-3 whitespace-pre-line">{{ $blueprint->notes }}</p>
        @endif
    </x-ui.card>
</div>
@endsection
