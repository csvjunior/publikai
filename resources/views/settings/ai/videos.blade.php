@extends('layouts.app')

@section('title', 'IA — Vídeos')
@section('header', 'IA')
@section('content')
<x-ui.page-header
    title="Vídeos de teste"
    description="Validação do provider de geração de vídeo image-to-video (Omni Flash)."
    :breadcrumbs="[['label' => 'Sistema'], ['label' => 'IA', 'url' => route('settings.ai')], ['label' => 'Vídeos']]"
/>

@if (session('video_test') && ! session('video_test')['ok'])
    <x-ui.alert variant="danger" title="Falha na geração" class="mb-6">
        {{ session('video_test')['message'] }}
        <span class="t-small">Código: {{ session('video_test')['error_code'] }}</span>
    </x-ui.alert>
@endif

@if (session('video_test') && (session('video_test')['started'] ?? false))
    <x-ui.alert variant="info" title="Geração iniciada" class="mb-6">
        {{ session('video_test')['message'] }}
    </x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Provider">
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Provider</dt>
                <dd class="t-body mt-0.5 font-medium text-ink">{{ $provider }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Modelo</dt>
                <dd class="t-body mt-0.5 font-medium text-ink">{{ $model }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Configuração</dt>
                <dd class="mt-0.5">
                    @if ($configured)
                        <x-ui.badge variant="success">Configurado</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning">Não configurado</x-ui.badge>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Fila</dt>
                <dd class="t-body mt-0.5">Worker necessário: <code>php artisan queue:work --tries=1 --timeout=600</code></dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Gerar vídeo de teste" description="Image-to-video a partir de um asset existente. Clipes curtos de 8s.">
        <form method="POST" action="{{ route('settings.ai.videos.store') }}" novalidate>
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="source_media_asset_id" class="t-label">
                        Imagem base <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select
                        id="source_media_asset_id"
                        name="source_media_asset_id"
                        required
                        class="mt-1 w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink focus:border-primary focus:ring-1 focus:ring-primary border-border"
                    >
                        <option value="">Selecione…</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" @selected((string) old('source_media_asset_id') === (string) $source->id)>#{{ $source->id }} · {{ $source->mime_type }} · {{ $source->width }}×{{ $source->height }}</option>
                        @endforeach
                    </select>
                    @error('source_media_asset_id')
                        <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <x-ui.textarea label="Prompt" name="prompt" :rows="4" required helper="Descreva o movimento. Sem pessoa real, sem marca existente." :value="old('prompt')" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select label="Proporção" name="aspect_ratio" :options="['9:16' => '9:16 (padrão)', '16:9' => '16:9']" :value="old('aspect_ratio', '9:16')" />
                    <x-ui.select label="Duração" name="duration_seconds" :options="['8' => '8s (padrão)']" :value="old('duration_seconds', '8')" />
                </div>
                @if ($configured)
                    <x-ui.button variant="primary" type="submit">Gerar vídeo de teste</x-ui.button>
                @else
                    <x-ui.button variant="primary" type="button" disabled>Gerar vídeo de teste</x-ui.button>
                    <p class="t-small mt-2">Defina <code>GOOGLE_AI_VIDEO_ENABLED=true</code> no <code>.env</code> (usa a mesma Auth Key do texto/imagem) para habilitar.</p>
                @endif
            </div>
        </form>
    </x-ui.card>

    @if ($recent->isNotEmpty())
        <x-ui.card title="Requisições recentes">
            <p class="t-small mb-3">Acompanhe o processamento. Atualize a página para ver o resultado (worker precisa estar rodando).</p>
            <ul class="space-y-2">
                @foreach ($recent as $generation)
                    <li class="flex flex-wrap items-center gap-2 text-sm">
                        <x-ui.badge :variant="$generation->status->value === 'failed' ? 'danger' : ($generation->status->value === 'success' ? 'success' : 'info')">{{ $generation->status->label() }}</x-ui.badge>
                        <span class="min-w-0 flex-1 truncate text-ink-secondary">{{ \Illuminate\Support\Str::limit($generation->prompt, 80) }}</span>
                        <span class="text-ink-muted">{{ $generation->created_at?->display() }}</span>
                        @if ($generation->status->value === 'success' && $generation->mediaAsset?->url())
                            <a href="{{ $generation->mediaAsset->url() }}" target="_blank" rel="noopener" class="font-medium text-primary hover:text-primary-hover">Ver vídeo</a>
                        @endif
                        @if ($generation->status->value === 'failed' && $generation->error_code)
                            <span class="text-ink-muted">({{ $generation->error_code }})</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
</div>
@endsection
