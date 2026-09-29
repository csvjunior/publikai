{{-- Formulário de perfil de referência (create/edit). Blocos por seção; grids no desktop, uma coluna no mobile. --}}
@php
    $isEdit = isset($profile) && $profile->exists;
    // Operator não altera status de perfil arquivado: exibe fixo, sem opção de saída.
    $statusLocked = $isEdit && $profile->isArchived()
        && \Illuminate\Support\Facades\Gate::denies('updateStatus', [$profile, \App\Enums\ReferenceProfileStatus::Active->value]);

    $statusOptions = [];
    $platformOptions = [];
    if (! $statusLocked) {
        foreach (\App\Enums\ReferenceProfileStatus::cases() as $case) {
            if (\Illuminate\Support\Facades\Gate::allows('updateStatus', [$profile ?? new \App\Models\ReferenceProfile, $case->value])) {
                $statusOptions[$case->value] = $case->label();
            }
        }
    }
    foreach (\App\Enums\SocialPlatform::cases() as $case) {
        $platformOptions[$case->value] = $case->label();
    }
@endphp

<div class="space-y-6">
    <x-ui.card title="Identificação" description="Perfil/canal externo usado como referência.">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input label="Nome" name="name" type="text" required autofocus helper="Ex.: Beauty Creator US." :value="old('name', $profile->name ?? null)" />
            </div>
            <x-ui.select label="Plataforma" name="platform" :options="$platformOptions" required :value="old('platform', isset($profile) ? $profile->platform->value : null)" />
            <x-ui.input label="Username" name="username" type="text" helper="Sem @." :value="old('username', $profile->username ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="URL do perfil" name="profile_url" type="url" required :value="old('profile_url', $profile->profile_url ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Contexto" description="Idioma, mercado e nicho observados.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.select label="Idioma" name="language" :options="config('locale-options.languages')" placeholder="Selecionar…" :value="old('language', $profile->language ?? null)" />
            <x-ui.select label="Mercado" name="market" :options="config('locale-options.markets')" placeholder="Selecionar…" :value="old('market', $profile->market ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Nicho" name="niche" type="text" helper="Ex.: Beauty / Skincare." :value="old('niche', $profile->niche ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Análise manual" description="Por que este perfil é uma referência (observação humana, sem IA).">
        <x-ui.textarea label="Por que este perfil é uma referência?" name="reason" :rows="3" :value="old('reason', $profile->reason ?? null)" />
    </x-ui.card>

    <x-ui.card title="Controle" description="Status e observações internas.">
        <div class="space-y-4">
            @if ($statusLocked)
                <div>
                    <p class="t-label">Status</p>
                    <p class="mt-1"><x-ui.badge variant="neutral">Arquivado</x-ui.badge></p>
                    <input type="hidden" name="status" value="archived">
                    <p class="t-small mt-1">Somente um administrador pode reativar esta referência.</p>
                </div>
            @else
                <x-ui.select label="Status" name="status" :options="$statusOptions" required :value="old('status', isset($profile) ? $profile->status->value : 'active')" />
            @endif
            <x-ui.textarea label="Observações" name="notes" :rows="3" :value="old('notes', $profile->notes ?? null)" />
        </div>
    </x-ui.card>

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <x-ui.button :href="$isEdit ? route('references.show', $profile) : route('references.index')" variant="outline">Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit">{{ $isEdit ? 'Salvar alterações' : 'Cadastrar referência' }}</x-ui.button>
    </div>
</div>
