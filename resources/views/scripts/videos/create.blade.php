@extends('layouts.app')

@section('title', 'Criar vídeo')
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    :title="'Criar vídeo: '.$script->title"
    description="Anime a imagem base em um clipe curto. A imagem original permanece intacta."
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => $script->title, 'url' => route('scripts.show', $script)], ['label' => 'Criar vídeo']]"
/>

@if (session('video_notice'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('video_notice') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Imagem base" description="Quadro inicial do clipe. Não será alterada.">
        <div class="flex flex-col gap-4 sm:flex-row">
            <span class="block w-full max-w-44 shrink-0">
                <img src="{{ $source->url() }}" alt="Imagem base" class="aspect-[9/16] w-full rounded-lg object-cover">
            </span>
            <dl class="grid min-w-0 flex-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Roteiro</dt>
                    <dd class="t-body mt-0.5">{{ $script->title }} · {{ $script->status->label() }}</dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Avatar</dt>
                    <dd class="t-body mt-0.5">{{ $script->avatar->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Dimensões</dt>
                    <dd class="t-body mt-0.5">{{ $source->width }} × {{ $source->height }} · {{ $source->mime_type }}</dd>
                </div>
            </dl>
        </div>
    </x-ui.card>

    <x-ui.card title="Movimento desejado" description="Descreva o movimento em linguagem simples. Ex.: personagem anda até a câmera, câmera se move suavemente, personagem olha ao redor.">
        <form method="POST" action="{{ route('scripts.videos.store', $script) }}" data-once novalidate>
            @csrf
            <input type="hidden" name="source_media_asset_id" value="{{ $source->id }}">
            <div class="space-y-4">
                <x-ui.textarea label="Movimento" name="motion" :rows="5" required :value="old('motion')" />
                <input type="hidden" name="aspect_ratio" value="9:16">
                <input type="hidden" name="duration_seconds" value="8">
                <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                    <div>
                        <dt class="t-small font-medium uppercase tracking-wide">Formato</dt>
                        <dd class="t-body mt-0.5">9:16 vertical</dd>
                    </div>
                    <div>
                        <dt class="t-small font-medium uppercase tracking-wide">Duração</dt>
                        <dd class="t-body mt-0.5">Clipe curto (~8–10s)</dd>
                    </div>
                </dl>
                @if ($aiConfigured)
                    <x-ui.button variant="ai" type="submit">Gerar vídeo</x-ui.button>
                    <p class="t-small mt-2">A geração continuará em segundo plano e pode levar alguns minutos.</p>
                @else
                    <x-ui.button variant="ai" type="button" disabled>Gerar vídeo</x-ui.button>
                    <p class="t-small mt-2">Configure a geração de vídeos em Sistema → IA.</p>
                @endif
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
