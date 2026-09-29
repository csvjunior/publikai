{{-- Formulário de avatar (create/edit). Blocos por seção; grids no desktop, uma coluna no mobile. Sem upload nesta Sprint. --}}
@php
    $isEdit = isset($avatar) && $avatar->exists;
    // Operator não altera status de avatar arquivado: exibe fixo, sem opção de saída.
    $statusLocked = $isEdit && $avatar->isArchived()
        && \Illuminate\Support\Facades\Gate::denies('updateStatus', [$avatar, \App\Enums\AvatarStatus::Active->value]);

    $statusOptions = [];
    if (! $statusLocked) {
        foreach (\App\Enums\AvatarStatus::cases() as $case) {
            if (\Illuminate\Support\Facades\Gate::allows('updateStatus', [$avatar ?? new \App\Models\Avatar, $case->value])) {
                $statusOptions[$case->value] = $case->label();
            }
        }
    }
@endphp

<div class="space-y-6">
    <x-ui.card title="Identidade" description="Quem é este personagem.">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input label="Nome" name="name" type="text" required autofocus helper="Ex.: Emma." :value="old('name', $avatar->name ?? null)" />
            </div>
            <x-ui.input label="Idade aparente" name="apparent_age" type="text" helper="Ex.: 27." :value="old('apparent_age', $avatar->apparent_age ?? null)" />
            <x-ui.input label="Apresentação de gênero" name="gender_presentation" type="text" helper="Ex.: Female." :value="old('gender_presentation', $avatar->gender_presentation ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Aparência" description="Características visuais para consistência do personagem.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Descrição étnico-visual" name="ethnicity_description" type="text" :value="old('ethnicity_description', $avatar->ethnicity_description ?? null)" />
            <x-ui.input label="Cabelo" name="hair" type="text" helper="Ex.: Brunette, medium length." :value="old('hair', $avatar->hair ?? null)" />
            <x-ui.input label="Olhos" name="eyes" type="text" helper="Ex.: Brown." :value="old('eyes', $avatar->eyes ?? null)" />
            <x-ui.input label="Pele" name="skin" type="text" helper="Ex.: Natural warm complexion." :value="old('skin', $avatar->skin ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Descrição corporal" name="body_description" type="text" :value="old('body_description', $avatar->body_description ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Estilo" description="Vestuário, estética e cenários preferenciais.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Vestuário padrão" name="default_clothing" type="text" helper="Ex.: Casual neutral outfits." :value="old('default_clothing', $avatar->default_clothing ?? null)" />
            <x-ui.input label="Estilo visual" name="visual_style" type="text" helper="Ex.: Natural UGC creator." :value="old('visual_style', $avatar->visual_style ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Cenários preferenciais" name="preferred_scenarios" type="text" helper="Ex.: Bedroom, bathroom, vanity, natural daylight." :value="old('preferred_scenarios', $avatar->preferred_scenarios ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Voz" description="Identidade de voz para futura geração (sem TTS nesta Sprint).">
        <x-ui.input label="Descrição de voz" name="voice_description" type="text" helper="Ex.: Young American female, friendly, relaxed." :value="old('voice_description', $avatar->voice_description ?? null)" />
    </x-ui.card>

    <x-ui.card title="Mercado" description="Onde e em que idioma o avatar atua.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.select label="Idioma" name="language" :options="config('locale-options.languages')" placeholder="Selecionar…" :value="old('language', $avatar->language ?? null)" />
            <x-ui.select label="Mercado" name="market" :options="config('locale-options.markets')" placeholder="Selecionar…" :value="old('market', $avatar->market ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Referência" description="Instruções para uso futuro de imagens de referência (sem upload nesta Sprint).">
        <x-ui.textarea label="Notas de referência visual" name="reference_notes" :rows="3" helper="Ex.: Use approved Emma reference images in future generation." :value="old('reference_notes', $avatar->reference_notes ?? null)" />
    </x-ui.card>

    <x-ui.card title="Controle" description="Status e observações internas.">
        <div class="space-y-4">
            @if ($statusLocked)
                <div>
                    <p class="t-label">Status</p>
                    <p class="mt-1"><x-ui.badge variant="neutral">Arquivado</x-ui.badge></p>
                    <input type="hidden" name="status" value="archived">
                    <p class="t-small mt-1">Somente um administrador pode reativar este avatar.</p>
                </div>
            @else
                <x-ui.select label="Status" name="status" :options="$statusOptions" required :value="old('status', isset($avatar) ? $avatar->status->value : 'active')" />
            @endif
            <x-ui.textarea label="Observações" name="notes" :rows="3" :value="old('notes', $avatar->notes ?? null)" />
        </div>
    </x-ui.card>

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <x-ui.button :href="$isEdit ? route('avatars.show', $avatar) : route('avatars.index')" variant="outline">Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit">{{ $isEdit ? 'Salvar alterações' : 'Criar avatar' }}</x-ui.button>
    </div>
</div>
