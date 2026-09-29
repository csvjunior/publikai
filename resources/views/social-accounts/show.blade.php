@extends('layouts.app')

@section('title', $account->name)
@section('header', 'Contas')
@section('content')
<x-ui.page-header
    :title="$account->name"
    :description="'@'.$account->username"
    :breadcrumbs="[['label' => 'Contas', 'url' => route('social-accounts.index')], ['label' => $account->name]]"
>
    <x-slot:actions>
        <x-ui.button :href="route('social-accounts.edit', $account)" variant="outline">Editar</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Conta">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :variant="$account->platform->badgeVariant()">{{ $account->platform->label() }}</x-ui.badge>
            <x-ui.badge :variant="$account->status->badgeVariant()">{{ $account->status->label() }}</x-ui.badge>
            @if ($account->market)
                <x-ui.badge variant="info">{{ $account->market }}</x-ui.badge>
            @endif
            @if ($account->language)
                <x-ui.badge variant="neutral">{{ config('locale-options.languages')[$account->language] ?? $account->language }}</x-ui.badge>
            @endif
        </div>

        <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Username</dt>
                <dd class="t-body mt-0.5 font-medium text-ink">{{ '@'.$account->username }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Idioma / Mercado</dt>
                <dd class="t-body mt-0.5">{{ $account->language ?? '—' }} · {{ $account->market ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">URL do perfil</dt>
                <dd class="t-body mt-0.5 min-w-0">
                    @if ($account->profile_url)
                        <span class="block truncate" title="{{ $account->profile_url }}">{{ $account->profile_url }}</span>
                        <a href="{{ $account->profile_url }}" target="_blank" rel="noopener" class="font-medium text-primary hover:text-primary-hover">Abrir perfil</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            @if ($account->notes)
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Observações</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $account->notes }}</dd>
                </div>
            @endif
        </dl>
    </x-ui.card>

    <x-ui.card title="Account DNA" description="Diretrizes operacionais desta conta para o futuro Content Engine.">
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Nicho</dt>
                <dd class="t-body mt-0.5">{{ $account->niche ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Público</dt>
                <dd class="t-body mt-0.5">{{ $account->audience ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Tom</dt>
                <dd class="t-body mt-0.5">{{ $account->tone ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Estilo de conteúdo</dt>
                <dd class="t-body mt-0.5">{{ $account->content_style ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">CTA padrão</dt>
                <dd class="t-body mt-0.5">{{ $account->default_cta ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Frequência de postagem</dt>
                <dd class="t-body mt-0.5">{{ $account->posting_frequency ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Persona padrão</dt>
                <dd class="t-body mt-0.5">
                    @if ($account->defaultPersona)
                        <a href="{{ route('personas.show', $account->defaultPersona) }}" class="font-medium text-primary hover:text-primary-hover">{{ $account->defaultPersona->name }}</a>
                    @else
                        Não definida
                    @endif
                </dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Avatar padrão</dt>
                <dd class="t-body mt-0.5">
                    @if ($account->defaultAvatar)
                        <a href="{{ route('avatars.show', $account->defaultAvatar) }}" class="font-medium text-primary hover:text-primary-hover">{{ $account->defaultAvatar->name }}</a>
                    @else
                        Não definido
                    @endif
                </dd>
            </div>
        </dl>
    </x-ui.card>
</div>
@endsection
