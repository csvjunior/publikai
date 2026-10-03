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

    <x-ui.card title="Imagem de referência" description="Auxílio de consistência visual do personagem artificial.">
        @if ($avatar->referenceImage)
            <div class="flex flex-col gap-4 sm:flex-row">
                <a href="{{ $avatar->referenceImage->url() }}" target="_blank" rel="noopener" class="block w-full max-w-44 shrink-0">
                    <img src="{{ $avatar->referenceImage->url() }}" alt="Referência do avatar" loading="lazy" class="aspect-[3/4] w-full rounded-lg object-cover">
                </a>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1">
                        <x-ui.badge variant="ai">Ativa</x-ui.badge>
                    </div>
                    <p class="t-muted mt-1">{{ $avatar->referenceImage->width }} × {{ $avatar->referenceImage->height }} · {{ $avatar->referenceImage->mime_type }}@if (\App\Support\FileSize::format($avatar->referenceImage->size_bytes)) · {{ \App\Support\FileSize::format($avatar->referenceImage->size_bytes) }}@endif</p>
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                        <a href="{{ $avatar->referenceImage->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir imagem</a>
                        <a href="{{ route('avatars.reference.create', $avatar) }}" class="text-xs font-medium text-ink-secondary hover:text-ink">Substituir</a>
                        <form method="POST" action="{{ route('avatars.reference.destroy', $avatar) }}" onsubmit="return confirm('Remover a imagem de referência?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-danger hover:opacity-80">Remover referência</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <x-ui.empty-state
                title="Nenhuma imagem de referência"
                description="Adicione uma imagem aprovada para ajudar a manter a aparência deste Avatar consistente entre gerações."
            />
            <div class="mt-4">
                <x-ui.button :href="route('avatars.reference.create', $avatar)" variant="outline">Adicionar referência</x-ui.button>
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
