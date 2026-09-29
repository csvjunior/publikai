{{-- Formulário de conta social (create/edit). Blocos por seção; grids no desktop, uma coluna no mobile. --}}
@php
    $isEdit = isset($account) && $account->exists;
    // Operator não altera status de conta arquivada: exibe fixo, sem opção de saída.
    $statusLocked = $isEdit && $account->isArchived()
        && \Illuminate\Support\Facades\Gate::denies('updateStatus', [$account, \App\Enums\SocialAccountStatus::Active->value]);

    $statusOptions = [];
    $platformOptions = [];
    if (! $statusLocked) {
        foreach (\App\Enums\SocialAccountStatus::cases() as $case) {
            if (\Illuminate\Support\Facades\Gate::allows('updateStatus', [$account ?? new \App\Models\SocialAccount, $case->value])) {
                $statusOptions[$case->value] = $case->label();
            }
        }
    }
    foreach (\App\Enums\SocialPlatform::cases() as $case) {
        $platformOptions[$case->value] = $case->label();
    }
@endphp

<div class="space-y-6">
    <x-ui.card title="Identificação" description="Conta/perfil gerenciado pelo Publikai.">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input label="Nome" name="name" type="text" required autofocus helper="Ex.: Beauty Finds US." :value="old('name', $account->name ?? null)" />
            </div>
            <x-ui.select label="Plataforma" name="platform" :options="$platformOptions" required :value="old('platform', isset($account) ? $account->platform->value : null)" />
            <x-ui.input label="Username" name="username" type="text" required helper="Sem @ — exibimos com @ automaticamente." :value="old('username', $account->username ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="URL do perfil" name="profile_url" type="url" :value="old('profile_url', $account->profile_url ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Mercado" description="Onde e em que idioma a conta opera.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.select label="Idioma" name="language" :options="config('locale-options.languages')" placeholder="Selecionar…" :value="old('language', $account->language ?? null)" />
            <x-ui.select label="Mercado" name="market" :options="config('locale-options.markets')" placeholder="Selecionar…" :value="old('market', $account->market ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Account DNA" description="Diretrizes operacionais que orientarão o futuro Content Engine.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Nicho" name="niche" type="text" helper="Ex.: Beauty / Skincare." :value="old('niche', $account->niche ?? null)" />
            <x-ui.input label="Público" name="audience" type="text" helper="Ex.: Women 20–40." :value="old('audience', $account->audience ?? null)" />
            <x-ui.input label="Tom" name="tone" type="text" helper="Ex.: Natural, casual, friendly." :value="old('tone', $account->tone ?? null)" />
            <x-ui.input label="Estilo de conteúdo" name="content_style" type="text" helper="Ex.: UGC, product discovery." :value="old('content_style', $account->content_style ?? null)" />
            <x-ui.input label="CTA padrão" name="default_cta" type="text" helper="Ex.: Check the link in bio." :value="old('default_cta', $account->default_cta ?? null)" />
            <x-ui.input label="Frequência de postagem" name="posting_frequency" type="text" helper="Ex.: 2 Reels/day." :value="old('posting_frequency', $account->posting_frequency ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Controle" description="Status e observações internas.">
        <div class="space-y-4">
            @if ($statusLocked)
                <div>
                    <p class="t-label">Status</p>
                    <p class="mt-1"><x-ui.badge variant="neutral">Arquivada</x-ui.badge></p>
                    <input type="hidden" name="status" value="archived">
                    <p class="t-small mt-1">Somente um administrador pode reativar esta conta.</p>
                </div>
            @else
                <x-ui.select label="Status" name="status" :options="$statusOptions" required :value="old('status', isset($account) ? $account->status->value : 'active')" />
            @endif
            <x-ui.textarea label="Observações" name="notes" :rows="3" :value="old('notes', $account->notes ?? null)" />
        </div>
    </x-ui.card>

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <x-ui.button :href="$isEdit ? route('social-accounts.show', $account) : route('social-accounts.index')" variant="outline">Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit">{{ $isEdit ? 'Salvar alterações' : 'Cadastrar conta' }}</x-ui.button>
    </div>
</div>
