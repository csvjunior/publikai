@extends('layouts.app')

@section('title', 'IA — Imagens')
@section('header', 'IA')
@section('content')
<x-ui.page-header
    title="Imagens de teste"
    description="Validação do provider de geração de imagem (Nano Banana 2)."
    :breadcrumbs="[['label' => 'Sistema'], ['label' => 'IA', 'url' => route('settings.ai')], ['label' => 'Imagens']]"
/>

@if (session('image_test') && ! session('image_test')['ok'])
    <x-ui.alert variant="danger" title="Falha na geração" class="mb-6">
        {{ session('image_test')['message'] }}
        <span class="t-small">Código: {{ session('image_test')['error_code'] }}</span>
    </x-ui.alert>
@endif

@if (session('image_test') && (session('image_test')['started'] ?? false))
    <x-ui.alert variant="info" title="Geração iniciada" class="mb-6">
        {{ session('image_test')['message'] }}
    </x-ui.alert>
@endif

<div class="space-y-6">
    @if ($preview)
        <x-ui.card title="Resultado">
            <img src="{{ $preview->url() }}" alt="Imagem gerada para teste" class="mx-auto max-h-96 rounded-lg">
            <dl class="mt-4 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Modelo</dt>
                    <dd class="t-body mt-0.5">{{ $preview->model ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Proporção</dt>
                    <dd class="t-body mt-0.5">{{ $preview->aspect_ratio ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Formato</dt>
                    <dd class="t-body mt-0.5">{{ $preview->mime_type ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Dimensões</dt>
                    <dd class="t-body mt-0.5">{{ $preview->width && $preview->height ? $preview->width.'×'.$preview->height : '—' }}</dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Tamanho</dt>
                    <dd class="t-body mt-0.5">{{ $preview->size_bytes ? number_format($preview->size_bytes / 1024, 1, ',', '.').' KB' : '—' }}</dd>
                </div>
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Arquivo</dt>
                    <dd class="t-body mt-0.5"><a href="{{ $preview->url() }}" target="_blank" rel="noopener" class="font-medium text-primary hover:text-primary-hover">Abrir imagem</a></dd>
                </div>
            </dl>
        </x-ui.card>
    @endif

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
        </dl>
    </x-ui.card>

    <x-ui.card title="Gerar imagem de teste" description="Text-to-image simples. Sem referência, sem edição.">
        <form method="POST" action="{{ route('settings.ai.images.store') }}" novalidate>
            @csrf
            <div class="space-y-4">
                <x-ui.textarea label="Prompt" name="prompt" :rows="4" required helper="Descreva a cena. Sem pessoa real, sem marca existente." :value="old('prompt')" />
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.select label="Proporção" name="aspect_ratio" :options="['1:1' => '1:1', '4:5' => '4:5', '9:16' => '9:16 (padrão)', '16:9' => '16:9']" :value="old('aspect_ratio', '9:16')" />
                    <x-ui.select label="Tamanho" name="image_size" :options="['1K' => '1K (padrão)']" :value="old('image_size', '1K')" />
                    <x-ui.select label="Formato" name="mime_type" :options="['image/jpeg' => 'JPEG (padrão)', 'image/png' => 'PNG']" :value="old('mime_type', 'image/jpeg')" />
                </div>
                @if ($configured)
                    <x-ui.button variant="primary" type="submit">Gerar imagem de teste</x-ui.button>
                @else
                    <x-ui.button variant="primary" type="button" disabled>Gerar imagem de teste</x-ui.button>
                    <p class="t-small mt-2">Defina <code>GOOGLE_AI_IMAGE_ENABLED=true</code> no <code>.env</code> (usa a mesma Auth Key do texto) para habilitar.</p>
                @endif
            </div>
        </form>
    </x-ui.card>

    @if ($recent->isNotEmpty())
        <x-ui.card title="Últimas imagens">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($recent as $asset)
                    <div>
                        <a href="{{ route('settings.ai.images', ['preview' => $asset->id]) }}">
                            <img src="{{ $asset->url() }}" alt="Miniatura de imagem gerada" loading="lazy" class="aspect-[9/16] w-full rounded-lg object-cover">
                        </a>
                        <p class="t-small mt-1 truncate">{{ $asset->created_at?->display() }}</p>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    @if ($generations->isNotEmpty())
        <x-ui.card title="Gerações recentes">
            <p class="t-small mb-3">Acompanhe o processamento. Atualize a página para ver o resultado (worker precisa estar rodando).</p>
            <ul class="space-y-2">
                @foreach ($generations as $generation)
                    <li class="flex flex-wrap items-center gap-2 text-sm">
                        <x-ui.badge :variant="$generation->status->badgeVariant()">{{ $generation->status->label() }}</x-ui.badge>
                        <span class="min-w-0 flex-1 truncate text-ink-secondary">{{ \Illuminate\Support\Str::limit($generation->prompt, 80) }}</span>
                        <span class="text-ink-muted">{{ $generation->created_at?->display() }}</span>
                        @if ($generation->status->value === 'success' && $generation->media_asset_id)
                            <a href="{{ route('settings.ai.images', ['preview' => $generation->media_asset_id]) }}" class="font-medium text-primary hover:text-primary-hover">Ver imagem</a>
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
