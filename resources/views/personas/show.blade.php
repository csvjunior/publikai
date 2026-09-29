@extends('layouts.app')

@section('title', $persona->name)
@section('header', 'Personas')
@section('content')
<x-ui.page-header
    :title="$persona->name"
    :description="$persona->audience"
    :breadcrumbs="[['label' => 'Personas', 'url' => route('personas.index')], ['label' => $persona->name]]"
>
    <x-slot:actions>
        <x-ui.button :href="route('personas.edit', $persona)" variant="outline">Editar</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Persona">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :variant="$persona->status->badgeVariant()">{{ $persona->status->label() }}</x-ui.badge>
            @if ($persona->market)
                <x-ui.badge variant="info">{{ $persona->market }}</x-ui.badge>
            @endif
            @if ($persona->language)
                <x-ui.badge variant="neutral">{{ config('locale-options.languages')[$persona->language] ?? $persona->language }}</x-ui.badge>
            @endif
        </div>

        @if ($persona->notes)
            <p class="t-body mt-4 whitespace-pre-line">{{ $persona->notes }}</p>
        @endif
    </x-ui.card>

    <x-ui.card title="Communication DNA" description="Diretrizes de voz e comportamento desta identidade.">
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Personality</dt>
                <dd class="t-body mt-0.5">{{ $persona->personality ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Tone</dt>
                <dd class="t-body mt-0.5">{{ $persona->tone ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Communication style</dt>
                <dd class="t-body mt-0.5">{{ $persona->communication_style ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Vocabulary</dt>
                <dd class="t-body mt-0.5">{{ $persona->vocabulary ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">CTA style</dt>
                <dd class="t-body mt-0.5">{{ $persona->default_cta_style ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Expressions</dt>
                <dd class="t-body mt-0.5 whitespace-pre-line">{{ $persona->expressions ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Content preferences</dt>
                <dd class="t-body mt-0.5 whitespace-pre-line">{{ $persona->content_preferences ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Avoidances</dt>
                <dd class="t-body mt-0.5 whitespace-pre-line">{{ $persona->avoidances ?? '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>
</div>
@endsection
