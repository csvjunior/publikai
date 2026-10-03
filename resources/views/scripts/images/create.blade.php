@extends('layouts.app')

@section('title', 'Gerar imagem')
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    :title="'Gerar imagem: '.$script->title"
    description="Revise o prompt visual montado a partir do roteiro e gere o asset."
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => $script->title, 'url' => route('scripts.show', $script)], ['label' => 'Gerar imagem']]"
/>

@if (session('image_notice'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('image_notice') }}</x-ui.alert>
@endif

@if (session('image_notice'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('image_notice') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Contexto" description="Resumo read-only do roteiro e suas relações.">
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Roteiro</dt>
                <dd class="t-body mt-0.5">{{ $script->title }} · {{ $script->status->label() }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Produto</dt>
                <dd class="t-body mt-0.5">{{ $script->product->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Blueprint</dt>
                <dd class="t-body mt-0.5">{{ $script->blueprint->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Persona</dt>
                <dd class="t-body mt-0.5">{{ $script->persona->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Avatar</dt>
                <dd class="t-body mt-0.5">{{ $script->avatar->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Imagem de referência</dt>
                <dd class="t-body mt-0.5">
                    @if ($script->avatar?->referenceImage)
                        <span class="inline-flex items-center gap-2">
                            <img src="{{ $script->avatar->referenceImage->url() }}" alt="Referência do avatar" class="h-10 w-8 rounded object-cover">
                            Sim
                        </span>
                    @else
                        Não
                    @endif
                </dd>
            </div>
        </dl>
        @if ($script->avatar?->referenceImage)
            <p class="t-small mt-3">Imagem de referência será usada para ajudar na consistência visual.</p>
        @else
            <p class="t-small mt-3">Este Avatar ainda não possui imagem de referência; a geração usará apenas o Visual DNA.</p>
        @endif
    </x-ui.card>

    <x-ui.card title="Prompt visual" description="Montado automaticamente. Revise e edite antes de gerar — a edição não altera o roteiro.">
        <form method="POST" action="{{ route('scripts.images.store', $script) }}" data-once novalidate>
            @csrf
            <div class="space-y-4">
                <x-ui.textarea label="Prompt" name="prompt" :rows="12" required :value="old('prompt', $prompt)" />
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-ui.select label="Proporção" name="aspect_ratio" :options="['1:1' => '1:1', '4:5' => '4:5', '9:16' => '9:16 (padrão)', '16:9' => '16:9']" :value="old('aspect_ratio', '9:16')" />
                    <x-ui.select label="Tamanho" name="image_size" :options="['1K' => '1K (padrão)']" :value="old('image_size', '1K')" />
                    <x-ui.select label="Formato" name="mime_type" :options="['image/jpeg' => 'JPEG (padrão)', 'image/png' => 'PNG']" :value="old('mime_type', 'image/jpeg')" />
                    <x-ui.select label="Finalidade" name="purpose" :options="['cover' => 'Capa', 'scene' => 'Cena (padrão)', 'product' => 'Produto', 'background' => 'Fundo', 'other' => 'Outro']" :value="old('purpose', 'scene')" />
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary')) class="h-4 w-4 rounded border-border accent-primary">
                    Definir como principal ao concluir
                </label>
                @if ($aiConfigured)
                    <x-ui.button variant="ai" type="submit">Gerar imagem</x-ui.button>
                @else
                    <x-ui.button variant="ai" type="button" disabled>Gerar imagem</x-ui.button>
                    <p class="t-small mt-2">Configure a geração de imagens em Sistema → IA.</p>
                @endif
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
