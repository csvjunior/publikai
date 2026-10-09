@extends('layouts.app')

@section('title', 'Criar variação')
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    :title="'Criar variação: '.$script->title"
    description="Transforme a imagem base em uma nova composição. A original permanece intacta."
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => $script->title, 'url' => route('scripts.show', $script)], ['label' => 'Criar variação']]"
/>

@if (session('image_notice'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('image_notice') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Imagem base" description="Imagem a transformar. Não será alterada.">
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
                <div>
                    <dt class="t-small font-medium uppercase tracking-wide">Referências visuais</dt>
                    <dd class="t-body mt-0.5">{{ $references->count() }} de {{ $maxReferences }}</dd>
                </div>
            </dl>
        </div>
        @if ($references->isEmpty())
            <p class="t-small mt-3">Este Avatar ainda não possui referências visuais; a variação usará a imagem base e o contexto.</p>
        @endif
    </x-ui.card>

    <x-ui.card title="Alteração desejada" description="Descreva a mudança em linguagem simples. Ex.: novo cenário, enquadramento, iluminação, pose ou roupa.">
        <form method="POST" action="{{ route('scripts.images.edit.store', [$script, $source]) }}" id="script-image-variation-form" data-once novalidate>
            @csrf
            <div class="space-y-4">
                <x-ui.textarea label="Mudança" name="change" :rows="5" required :value="old('change')" />
                @if ($references->isNotEmpty())
                    <div>
                        <p class="t-label">Referências visuais de apoio</p>
                        <div class="mt-1 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ($references as $reference)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-border p-2" data-reference-card>
                                    <input type="checkbox" name="reference_ids[]" value="{{ $reference->id }}" @checked(in_array($reference->id, old('reference_ids', $references->pluck('id')->all()))) class="h-4 w-4 shrink-0 rounded border-border accent-primary" data-reference-checkbox>
                                    <img src="{{ $reference->url() }}" alt="Referência do avatar" class="h-12 w-10 rounded object-cover">
                                    @if ($reference->pivot->is_primary)
                                        <span class="t-small font-medium">Principal</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-ink">
                            <input type="checkbox" name="visual_dna_only" value="1" @checked(old('visual_dna_only')) class="h-4 w-4 rounded border-border accent-primary" id="visual-dna-only">
                            Gerar somente com imagem base e contexto
                        </label>
                        <p class="t-small mt-1 hidden" id="visual-dna-only-helper">As referências visuais não serão usadas nesta variação.</p>
                    </div>
                @endif
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-ui.select label="Proporção" name="aspect_ratio" :options="['1:1' => '1:1', '4:5' => '4:5', '9:16' => '9:16', '16:9' => '16:9']" :value="old('aspect_ratio', $defaultAspectRatio)" />
                    <x-ui.select label="Tamanho" name="image_size" :options="['1K' => '1K (padrão)']" :value="old('image_size', '1K')" />
                    <x-ui.select label="Formato" name="mime_type" :options="['image/jpeg' => 'JPEG (padrão)', 'image/png' => 'PNG']" :value="old('mime_type', 'image/jpeg')" />
                    <x-ui.select label="Finalidade" name="purpose" :options="['cover' => 'Capa', 'scene' => 'Cena', 'product' => 'Produto', 'background' => 'Fundo', 'other' => 'Outro']" :value="old('purpose', $sourcePurpose)" />
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary')) class="h-4 w-4 rounded border-border accent-primary">
                    Definir como principal ao concluir
                </label>
                @if ($aiConfigured)
                    <x-ui.button variant="ai" type="submit">Gerar variação</x-ui.button>
                @else
                    <x-ui.button variant="ai" type="button" disabled>Gerar variação</x-ui.button>
                    <p class="t-small mt-2">Configure a geração de imagens em Sistema → IA.</p>
                @endif
            </div>
        </form>
    </x-ui.card>
</div>

<script>
(function () {
    var toggle = document.getElementById('visual-dna-only');
    if (! toggle) {
        return;
    }
    var boxes = Array.prototype.slice.call(document.querySelectorAll('[data-reference-checkbox]'));
    var helper = document.getElementById('visual-dna-only-helper');

    function sync() {
        var dnaOnly = toggle.checked;
        boxes.forEach(function (box) {
            box.disabled = dnaOnly;
            var card = box.closest('[data-reference-card]');
            if (card) {
                card.classList.toggle('opacity-50', dnaOnly);
            }
        });
        if (helper) {
            helper.classList.toggle('hidden', ! dnaOnly);
        }
    }

    toggle.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
