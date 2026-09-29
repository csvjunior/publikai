{{-- Formulário de persona (create/edit). Blocos por seção; grids no desktop, uma coluna no mobile. --}}
@php
    $isEdit = isset($persona) && $persona->exists;
    // Operator não altera status de persona arquivada: exibe fixo, sem opção de saída.
    $statusLocked = $isEdit && $persona->isArchived()
        && \Illuminate\Support\Facades\Gate::denies('updateStatus', [$persona, \App\Enums\PersonaStatus::Active->value]);

    $statusOptions = [];
    if (! $statusLocked) {
        foreach (\App\Enums\PersonaStatus::cases() as $case) {
            if (\Illuminate\Support\Facades\Gate::allows('updateStatus', [$persona ?? new \App\Models\Persona, $case->value])) {
                $statusOptions[$case->value] = $case->label();
            }
        }
    }
@endphp

<div class="space-y-6">
    <x-ui.card title="Identidade" description="Quem é esta voz e para quem ela fala.">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input label="Nome" name="name" type="text" required autofocus helper="Ex.: Emma US Beauty." :value="old('name', $persona->name ?? null)" />
            </div>
            <x-ui.select label="Idioma" name="language" :options="config('locale-options.languages')" placeholder="Selecionar…" :value="old('language', $persona->language ?? null)" />
            <x-ui.select label="Mercado" name="market" :options="config('locale-options.markets')" placeholder="Selecionar…" :value="old('market', $persona->market ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Público" name="audience" type="text" helper="Ex.: Women 20–40 interested in beauty." :value="old('audience', $persona->audience ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Personalidade" description="Comportamento e estilo de comunicação.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Personalidade" name="personality" type="text" helper="Ex.: Friendly, curious, confident." :value="old('personality', $persona->personality ?? null)" />
            <x-ui.input label="Tom" name="tone" type="text" helper="Ex.: Natural, casual, conversational." :value="old('tone', $persona->tone ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Estilo de comunicação" name="communication_style" type="text" helper="Ex.: First-person UGC and personal recommendation." :value="old('communication_style', $persona->communication_style ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Linguagem" description="Vocabulário e expressões típicas.">
        <div class="space-y-4">
            <x-ui.input label="Vocabulário" name="vocabulary" type="text" helper="Ex.: Everyday American English." :value="old('vocabulary', $persona->vocabulary ?? null)" />
            <x-ui.textarea label="Expressões" name="expressions" :rows="4" helper="Uma por linha. Ex.: I didn't expect this..." :value="old('expressions', $persona->expressions ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Diretrizes de conteúdo" description="O que buscar, o que evitar e como chamar para ação.">
        <div class="space-y-4">
            <x-ui.textarea label="Preferências" name="content_preferences" :rows="3" helper="Ex.: Curiosity, demonstration, problem/solution." :value="old('content_preferences', $persona->content_preferences ?? null)" />
            <x-ui.textarea label="Evitar" name="avoidances" :rows="3" helper="Ex.: Aggressive sales language." :value="old('avoidances', $persona->avoidances ?? null)" />
            <x-ui.input label="Estilo de CTA" name="default_cta_style" type="text" helper="Ex.: Soft recommendation, link in bio." :value="old('default_cta_style', $persona->default_cta_style ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Controle" description="Status e observações internas.">
        <div class="space-y-4">
            @if ($statusLocked)
                <div>
                    <p class="t-label">Status</p>
                    <p class="mt-1"><x-ui.badge variant="neutral">Arquivada</x-ui.badge></p>
                    <input type="hidden" name="status" value="archived">
                    <p class="t-small mt-1">Somente um administrador pode reativar esta persona.</p>
                </div>
            @else
                <x-ui.select label="Status" name="status" :options="$statusOptions" required :value="old('status', isset($persona) ? $persona->status->value : 'active')" />
            @endif
            <x-ui.textarea label="Observações" name="notes" :rows="3" :value="old('notes', $persona->notes ?? null)" />
        </div>
    </x-ui.card>

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <x-ui.button :href="$isEdit ? route('personas.show', $persona) : route('personas.index')" variant="outline">Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit">{{ $isEdit ? 'Salvar alterações' : 'Criar persona' }}</x-ui.button>
    </div>
</div>
