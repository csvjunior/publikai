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
    @if (in_array($statusLabel, ['Rascunho', 'Roteiro para revisar'], true))
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
                <x-ui.button :href="route('scripts.show', $script)" variant="ai">Produzir vídeo</x-ui.button>
                @if ($script->isEditable())
                    <x-ui.button :href="route('scripts.edit', $script)" variant="outline">Editar roteiro</x-ui.button>
                @endif
            </div>
        </x-ui.card>
    @endif

    @include('scripts.partials.production', ['script' => $script, 'flow' => $flow])

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
