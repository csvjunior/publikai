@extends('layouts.app')

@section('title', $avatar->name)
@section('header', 'Avatares')
@section('content')
<x-ui.page-header
    :title="$avatar->name"
    :description="$avatar->visual_style"
    :breadcrumbs="[['label' => 'Avatares', 'url' => route('avatars.index')], ['label' => $avatar->name]]"
>
    <x-slot:actions>
        <x-ui.button :href="route('avatars.edit', $avatar)" variant="outline">Editar</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Avatar">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :variant="$avatar->status->badgeVariant()">{{ $avatar->status->label() }}</x-ui.badge>
            @if ($avatar->market)
                <x-ui.badge variant="info">{{ $avatar->market }}</x-ui.badge>
            @endif
            @if ($avatar->language)
                <x-ui.badge variant="neutral">{{ config('locale-options.languages')[$avatar->language] ?? $avatar->language }}</x-ui.badge>
            @endif
        </div>

        <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Idade aparente</dt>
                <dd class="t-body mt-0.5">{{ $avatar->apparent_age ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Apresentação de gênero</dt>
                <dd class="t-body mt-0.5">{{ $avatar->gender_presentation ?? '—' }}</dd>
            </div>
        </dl>

        @if ($avatar->notes)
            <p class="t-body mt-4 whitespace-pre-line">{{ $avatar->notes }}</p>
        @endif
    </x-ui.card>

    <x-ui.card title="Visual DNA" description="Diretrizes visuais desta identidade.">
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Cabelo</dt>
                <dd class="t-body mt-0.5">{{ $avatar->hair ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Olhos</dt>
                <dd class="t-body mt-0.5">{{ $avatar->eyes ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Pele</dt>
                <dd class="t-body mt-0.5">{{ $avatar->skin ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Descrição étnico-visual</dt>
                <dd class="t-body mt-0.5">{{ $avatar->ethnicity_description ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Descrição corporal</dt>
                <dd class="t-body mt-0.5">{{ $avatar->body_description ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Vestuário padrão</dt>
                <dd class="t-body mt-0.5">{{ $avatar->default_clothing ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Estilo visual</dt>
                <dd class="t-body mt-0.5">{{ $avatar->visual_style ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Cenários preferenciais</dt>
                <dd class="t-body mt-0.5">{{ $avatar->preferred_scenarios ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Voz</dt>
                <dd class="t-body mt-0.5">{{ $avatar->voice_description ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Referência visual</dt>
                <dd class="t-body mt-0.5 whitespace-pre-line">{{ $avatar->reference_notes ?? '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>
</div>
@endsection
