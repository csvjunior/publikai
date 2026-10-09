@extends('layouts.app')

@section('title', 'Meus conteúdos')
@section('header', 'Conteúdo')
@section('content')
<x-ui.page-header
    title="Meus conteúdos"
    :breadcrumbs="[['label' => 'Conteúdo']]"
>
    <x-slot:actions>
        <x-ui.button :href="route('content.create')" variant="ai">Criar conteúdo</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if ($scripts->isEmpty())
    <x-ui.empty-state
        title="Nenhum conteúdo ainda"
        description="Crie seu primeiro vídeo ou imagem em menos de um minuto."
    />
    <div class="mt-4">
        <x-ui.button :href="route('content.create', ['type' => 'video'])" variant="ai">Criar vídeo</x-ui.button>
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($scripts as $script)
            <x-ui.card :title="$script->product?->name ?? $script->title" description="">
                <div class="flex flex-wrap items-center gap-1">
                    <x-ui.badge variant="ai">{{ $script->contentTypeLabel() }}</x-ui.badge>
                    <x-ui.badge variant="neutral">{{ $script->contentStatusLabel() }}</x-ui.badge>
                </div>
                <p class="t-small mt-2">
                    {{ $script->persona?->name ?? '—' }} · {{ $script->avatar?->name ?? '—' }} · {{ $script->created_at?->display() }}
                </p>
                <div class="mt-3">
                    <a href="{{ route('content.show', ['content' => $script]) }}" class="text-xs font-medium text-primary hover:text-primary-hover">Abrir conteúdo</a>
                </div>
            </x-ui.card>
        @endforeach
    </div>
    <div class="mt-6">
        {{ $scripts->links() }}
    </div>
@endif
@endsection
