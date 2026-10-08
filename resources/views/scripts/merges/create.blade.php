@extends('layouts.app')

@section('title', 'Adicionar narração ao vídeo')
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    :title="'Adicionar narração ao vídeo: '.$script->title"
    description="Combine um vídeo com uma narração. O vídeo define a duração; a narração é cortada ou seguida de silêncio."
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => $script->title, 'url' => route('scripts.show', $script)], ['label' => 'Adicionar narração']]"
/>

<div class="space-y-6">
    <x-ui.card title="Seleção" description="Um vídeo e uma narração deste roteiro.">
        @if ($videos->isEmpty() || $audios->isEmpty())
            <x-ui.empty-state
                title="Itens insuficientes"
                description="É necessário ao menos um vídeo e uma narração concluídos neste roteiro."
            />
        @else
            <form method="POST" action="{{ route('scripts.merges.store', $script) }}" data-once novalidate>
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="video_media_asset_id" class="t-label">
                            Vídeo <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <select
                            id="video_media_asset_id"
                            name="video_media_asset_id"
                            required
                            class="mt-1 w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink focus:border-primary focus:ring-1 focus:ring-primary border-border"
                            data-video-select
                        >
                            <option value="">Selecione…</option>
                            @foreach ($videos as $video)
                                <option value="{{ $video->id }}" data-duration="{{ $video->duration_seconds ?? '' }}" @selected((string) old('video_media_asset_id') === (string) $video->id)>Vídeo · {{ $video->duration_seconds ?? '—' }}s · {{ $video->width }}×{{ $video->height }}</option>
                            @endforeach
                        </select>
                        @error('video_media_asset_id')
                            <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="audio_media_asset_id" class="t-label">
                            Narração <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <select
                            id="audio_media_asset_id"
                            name="audio_media_asset_id"
                            required
                            class="mt-1 w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink focus:border-primary focus:ring-1 focus:ring-primary border-border"
                            data-audio-select
                        >
                            <option value="">Selecione…</option>
                            @foreach ($audios as $audio)
                                <option value="{{ $audio->id }}" data-duration="{{ $audio->duration_seconds ?? '' }}" @selected((string) old('audio_media_asset_id') === (string) $audio->id)>Narração · {{ $audio->duration_seconds ?? '—' }}s</option>
                            @endforeach
                        </select>
                        @error('audio_media_asset_id')
                            <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="rounded-lg border border-border bg-surface-muted p-3">
                        <p class="t-small font-medium">Resultado</p>
                        <p class="t-small mt-1" id="merge-comparison">Selecione vídeo e narração.</p>
                        <p class="t-small mt-1 hidden text-ink-secondary" id="merge-warning">A narração será cortada no final do vídeo.</p>
                    </div>
                    <div>
                        <x-ui.button variant="ai" type="submit">Gerar vídeo com narração</x-ui.button>
                        <p class="t-small mt-2">O processamento será realizado em segundo plano.</p>
                    </div>
                </div>
            </form>
        @endif
    </x-ui.card>
</div>

<script>
(function () {
    var video = document.querySelector('[data-video-select]');
    var audio = document.querySelector('[data-audio-select]');
    var comparison = document.getElementById('merge-comparison');
    var warning = document.getElementById('merge-warning');
    if (! video || ! audio || ! comparison) {
        return;
    }

    function durationOf(select) {
        var option = select.options[select.selectedIndex];
        var value = option ? parseFloat(option.getAttribute('data-duration')) : NaN;
        return isNaN(value) ? null : value;
    }

    function sync() {
        var videoDuration = durationOf(video);
        var audioDuration = durationOf(audio);

        if (videoDuration === null || audioDuration === null) {
            comparison.textContent = 'Selecione vídeo e narração.';
            warning.classList.add('hidden');
            return;
        }

        comparison.textContent = 'Vídeo: ' + videoDuration + 's · Narração: ' + audioDuration + 's · Resultado: ' + videoDuration + 's';

        if (audioDuration > videoDuration) {
            warning.classList.remove('hidden');
        } else {
            warning.classList.add('hidden');
        }
    }

    video.addEventListener('change', sync);
    audio.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
