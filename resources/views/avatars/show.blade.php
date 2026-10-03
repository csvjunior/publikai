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

    <x-ui.card title="Referências visuais" description="Auxílio de consistência visual do personagem artificial. {{ $avatar->referenceImages->count() }} de {{ $maxReferences }} referências.">
        @if ($avatar->referenceImages->isEmpty())
            <x-ui.empty-state
                title="Nenhuma referência visual"
                description="Adicione imagens aprovadas para ajudar a manter a aparência deste Avatar consistente entre gerações."
            />
            <div class="mt-4">
                <x-ui.button :href="route('avatars.reference.create', $avatar)" variant="outline">Adicionar referência</x-ui.button>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($avatar->referenceImages as $reference)
                    <div class="overflow-hidden rounded-card border border-border bg-surface shadow-card">
                        <a href="{{ $reference->url() }}" target="_blank" rel="noopener" class="block">
                            <img src="{{ $reference->url() }}" alt="Referência do avatar" loading="lazy" class="aspect-[3/4] w-full object-cover">
                        </a>
                        <div class="space-y-1 p-2.5">
                            @if ($reference->pivot->is_primary)
                                <div class="flex flex-wrap items-center gap-1">
                                    <x-ui.badge variant="ai">Principal</x-ui.badge>
                                </div>
                            @endif
                            <p class="t-muted">{{ $reference->width }} × {{ $reference->height }} · {{ $reference->mime_type }}@if (\App\Support\FileSize::format($reference->size_bytes)) · {{ \App\Support\FileSize::format($reference->size_bytes) }}@endif</p>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-0.5">
                                <a href="{{ $reference->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir imagem</a>
                                @if (! $reference->pivot->is_primary)
                                    <form method="POST" action="{{ route('avatars.references.primary', [$avatar, $reference]) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-ink-secondary hover:text-ink">Definir como principal</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('avatars.references.destroy', [$avatar, $reference]) }}" onsubmit="return confirm('Remover esta referência?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-danger hover:opacity-80">Remover</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                @if ($avatar->referenceImages->count() < $maxReferences)
                    <x-ui.button :href="route('avatars.reference.create', $avatar)" variant="outline">Adicionar referência</x-ui.button>
                @else
                    <x-ui.button variant="outline" type="button" disabled>Adicionar referência</x-ui.button>
                    <p class="t-small mt-2">Limite de referências atingido.</p>
                @endif
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
