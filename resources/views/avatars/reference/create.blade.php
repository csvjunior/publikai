@extends('layouts.app')

@section('title', 'Referência do Avatar')
@section('header', 'Avatares')
@section('content')
<x-ui.page-header
    :title="($avatar->reference_media_asset_id ? 'Substituir referência: ' : 'Adicionar referência: ').$avatar->name"
    description="Imagem aprovada para ajudar na consistência visual do personagem artificial."
    :breadcrumbs="[['label' => 'Avatares', 'url' => route('avatars.index')], ['label' => $avatar->name, 'url' => route('avatars.show', $avatar)], ['label' => 'Referência']]"
/>

<div class="space-y-6">
    <x-ui.card title="Avatar" description="Resumo read-only.">
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Avatar</dt>
                <dd class="t-body mt-0.5">{{ $avatar->name }} · {{ $avatar->status->label() }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Estilo visual</dt>
                <dd class="t-body mt-0.5">{{ $avatar->visual_style ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">Visual DNA</dt>
                <dd class="t-body mt-0.5">{{ trim(implode(' · ', array_filter([$avatar->hair, $avatar->eyes, $avatar->skin, $avatar->default_clothing]))) ?: '—' }}</dd>
            </div>
        </dl>
        @if ($avatar->referenceImage)
            <div class="mt-4 flex items-center gap-3">
                <img src="{{ $avatar->referenceImage->url() }}" alt="Referência atual" class="h-20 w-16 rounded-lg object-cover">
                <p class="t-small">Referência atual será substituída ao salvar.</p>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Imagem" description="JPEG ou PNG, até 10 MB, mínimo 512×512.">
        <form method="POST" action="{{ route('avatars.reference.store', $avatar) }}" enctype="multipart/form-data" data-once novalidate>
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="image" class="t-label">
                        Arquivo <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="image"
                        name="image"
                        type="file"
                        accept="image/jpeg,image/png"
                        required
                        class="mt-1 w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink file:mr-3 file:rounded-md file:border-0 file:bg-surface-muted file:px-3 file:py-1.5 file:text-sm file:font-medium focus:border-primary focus:ring-1 focus:ring-primary border-border"
                    >
                    @error('image')
                        <p class="mt-1 text-xs text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div id="reference-preview" class="hidden">
                    <img id="reference-preview-img" src="#" alt="Pré-visualização" class="h-40 w-32 rounded-lg object-cover">
                </div>
                <div>
                    <x-ui.button variant="ai" type="submit">Salvar referência</x-ui.button>
                </div>
            </div>
        </form>
    </x-ui.card>
</div>

<script>
document.getElementById('image').addEventListener('change', function (event) {
    var file = event.target.files && event.target.files[0];
    var box = document.getElementById('reference-preview');
    var img = document.getElementById('reference-preview-img');
    if (! file || ! file.type.match(/^image\//)) {
        box.classList.add('hidden');
        return;
    }
    img.src = URL.createObjectURL(file);
    box.classList.remove('hidden');
});
</script>
@endsection
