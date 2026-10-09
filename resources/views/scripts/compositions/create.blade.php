@extends('layouts.app')

@section('title', 'Montar vídeo')
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    :title="'Montar vídeo: '.$script->title"
    description="Combine clipes e imagens em um vídeo vertical 720×1280, 30fps, sem áudio."
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => $script->title, 'url' => route('scripts.show', $script)], ['label' => 'Montar vídeo']]"
/>

<div class="space-y-6">
    <x-ui.card title="Itens" description="Selecione, ordene por posição e ajuste cortes/durações. Máximo {{ $maxInputs }} itens.">
        @if ($eligible->isEmpty())
            <x-ui.empty-state
                title="Sem itens elegíveis"
                description="Este roteiro ainda não possui imagens ou vídeos prontos para compor."
            />
        @else
            <form method="POST" action="{{ route('scripts.compositions.store', $script) }}" data-once novalidate>
                @csrf
                <div class="space-y-3" id="composition-items">
                    @foreach ($eligible as $index => $asset)
                        <div class="flex flex-col gap-3 rounded-lg border border-border p-3 sm:flex-row sm:items-center" data-composition-item data-kind="{{ $asset->type->value }}" data-real-duration="{{ $asset->duration_seconds ?? '' }}">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" name="items[{{ $index }}][media_asset_id]" value="{{ $asset->id }}" @checked(in_array($asset->id, collect(old('items', []))->pluck('media_asset_id')->map(fn ($id) => (int) $id)->all())) class="h-4 w-4 rounded border-border accent-primary" data-item-check>
                                <span class="t-small font-medium">{{ $asset->type->value === 'video' ? 'Vídeo · '.($asset->duration_seconds ?? '—').'s' : 'Imagem · '.($asset->width && $asset->height ? $asset->width.'×'.$asset->height : ($asset->mime_type ?? '')) }}</span>
                            </label>
                            @if ($asset->type->value === 'video')
                                <span class="t-small">Duração real: {{ $asset->duration_seconds ?? '—' }}s</span>
                                <label class="t-small flex items-center gap-1">Início (s)
                                    <input type="number" name="items[{{ $index }}][trim_start_s]" value="0" min="0" step="0.1" class="w-20 rounded-lg border border-border bg-surface px-2 py-1 text-sm" data-trim-start>
                                </label>
                                <label class="t-small flex items-center gap-1">Fim (s)
                                    <input type="number" name="items[{{ $index }}][trim_end_s]" value="" min="0" step="0.1" placeholder="fim" class="w-20 rounded-lg border border-border bg-surface px-2 py-1 text-sm" data-trim-end>
                                </label>
                            @else
                                <img src="{{ $asset->url() }}" alt="Imagem elegível" loading="lazy" class="h-12 w-10 rounded object-cover">
                                <label class="t-small flex items-center gap-1">Duração (s)
                                    <input type="number" name="items[{{ $index }}][image_duration_s]" value="3" min="1" max="10" step="0.5" class="w-20 rounded-lg border border-border bg-surface px-2 py-1 text-sm" data-image-duration>
                                </label>
                            @endif
                            <label class="t-small flex items-center gap-1">Posição
                                <input type="number" name="items[{{ $index }}][position]" value="{{ $index + 1 }}" min="1" class="w-16 rounded-lg border border-border bg-surface px-2 py-1 text-sm" data-item-position>
                            </label>
                        </div>
                    @endforeach
                </div>
                <p class="t-small mt-2">Define a ordem em que os itens aparecerão no vídeo.</p>
                <div class="mt-3 rounded-lg border border-border bg-surface-muted p-3">
                    <p class="t-small font-medium">Resumo da montagem</p>
                    <ol id="composition-summary" class="t-small mt-1 list-decimal space-y-0.5 pl-5">
                        <li class="text-ink-muted">Nenhum item selecionado.</li>
                    </ol>
                </div>
                @error('assets')
                    <p class="mt-2 text-xs text-danger" role="alert">{{ $message }}</p>
                @enderror
                <div class="mt-4">
                    <x-ui.button variant="ai" type="submit">Iniciar composição</x-ui.button>
                    <p class="t-small mt-2">O processamento roda em segundo plano e pode levar alguns minutos.</p>
                </div>
            </form>
        @endif
    </x-ui.card>
</div>

<script>
(function () {
    var box = document.getElementById('composition-items');
    var summary = document.getElementById('composition-summary');
    if (! box || ! summary) {
        return;
    }

    function fmt(seconds) {
        var rounded = Math.round(seconds * 10) / 10;
        return (rounded % 1 === 0 ? rounded.toFixed(0) : rounded.toFixed(1)) + 's';
    }

    function sync() {
        var rows = Array.prototype.slice.call(box.querySelectorAll('[data-composition-item]'));
        var selected = rows.filter(function (row) {
            var check = row.querySelector('[data-item-check]');
            return check && check.checked;
        });
        selected.sort(function (a, b) {
            var pa = parseInt(a.querySelector('[data-item-position]').value, 10) || 0;
            var pb = parseInt(b.querySelector('[data-item-position]').value, 10) || 0;
            return pa - pb;
        });

        summary.innerHTML = '';

        if (selected.length === 0) {
            var empty = document.createElement('li');
            empty.className = 'text-ink-muted';
            empty.textContent = 'Nenhum item selecionado.';
            summary.appendChild(empty);
            return;
        }

        selected.forEach(function (row) {
            var kind = row.getAttribute('data-kind');
            var item = document.createElement('li');

            if (kind === 'video') {
                var start = parseFloat(row.querySelector('[data-trim-start]').value) || 0;
                var endInput = row.querySelector('[data-trim-end]').value;
                var real = parseFloat(row.getAttribute('data-real-duration')) || 0;
                var end = endInput === '' ? real : parseFloat(endInput);
                item.textContent = 'Vídeo — ' + fmt(start) + ' a ' + (endInput === '' ? 'fim' : fmt(end));
            } else {
                var duration = parseFloat(row.querySelector('[data-image-duration]').value) || 0;
                item.textContent = 'Imagem — ' + fmt(duration);
            }

            summary.appendChild(item);
        });
    }

    box.addEventListener('change', sync);
    box.addEventListener('input', sync);
    sync();
})();
</script>
@endsection
