{{-- Formulário de blueprint (create/edit). Blocos por seção; grids no desktop, uma coluna no mobile. Origem nunca editável aqui. --}}
@php
    $isEdit = isset($blueprint) && $blueprint->exists;
    // Operator não altera status de blueprint arquivado: exibe fixo, sem opção de saída.
    $statusLocked = $isEdit && $blueprint->isArchived()
        && \Illuminate\Support\Facades\Gate::denies('updateStatus', [$blueprint, \App\Enums\ContentBlueprintStatus::Active->value]);

    $statusOptions = [];
    if (! $statusLocked) {
        foreach (\App\Enums\ContentBlueprintStatus::cases() as $case) {
            if (\Illuminate\Support\Facades\Gate::allows('updateStatus', [$blueprint ?? new \App\Models\ContentBlueprint, $case->value])) {
                $statusOptions[$case->value] = $case->label();
            }
        }
    }
@endphp

<div class="space-y-6">
    <x-ui.card title="Identificação" description="Nome, descrição e categoria estrutural.">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input label="Nome" name="name" type="text" required autofocus helper="Ex.: Curiosity UGC Beauty US." :value="old('name', $blueprint->name ?? null)" />
            </div>
            <div class="sm:col-span-2">
                <x-ui.textarea label="Descrição" name="description" :rows="3" :value="old('description', $blueprint->description ?? null)" />
            </div>
            <x-ui.select label="Tipo" name="content_type" :options="config('references.content_types')" placeholder="Selecionar…" :value="old('content_type', $blueprint->content_type ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Estratégia" description="Objetivo, hook, estrutura e CTA em formato de padrão reutilizável.">
        <div class="space-y-4">
            <x-ui.input label="Objetivo" name="objective" type="text" helper="Ex.: Generate curiosity and product consideration." :value="old('objective', $blueprint->objective ?? null)" />
            <x-ui.input label="Hook" name="hook_pattern" type="text" helper="Padrão abstrato, nunca fala literal de criador." :value="old('hook_pattern', $blueprint->hook_pattern ?? null)" />
            <x-ui.textarea label="Estrutura" name="structure_pattern" :rows="3" helper="Ex.: Curiosity → problem → reveal → demonstration → soft CTA." :value="old('structure_pattern', $blueprint->structure_pattern ?? null)" />
            <x-ui.input label="CTA" name="cta_pattern" type="text" helper="Ex.: Soft recommendation + link in bio." :value="old('cta_pattern', $blueprint->cta_pattern ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Estilo" description="Estética, comunicação e duração recomendada.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Estilo visual" name="visual_style" type="text" :value="old('visual_style', $blueprint->visual_style ?? null)" />
            <x-ui.input label="Estilo de comunicação" name="communication_style" type="text" :value="old('communication_style', $blueprint->communication_style ?? null)" />
            <x-ui.input label="Duração recomendada (segundos)" name="recommended_duration_seconds" type="number" min="1" :value="old('recommended_duration_seconds', $blueprint->recommended_duration_seconds ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Contexto" description="Onde este blueprint se aplica.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.select label="Idioma" name="language" :options="config('locale-options.languages')" placeholder="Selecionar…" :value="old('language', $blueprint->language ?? null)" />
            <x-ui.select label="Mercado" name="market" :options="config('locale-options.markets')" placeholder="Selecionar…" :value="old('market', $blueprint->market ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Nicho" name="niche" type="text" :value="old('niche', $blueprint->niche ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Controle" description="Status e observações internas.">
        <div class="space-y-4">
            @if ($statusLocked)
                <div>
                    <p class="t-label">Status</p>
                    <p class="mt-1"><x-ui.badge variant="neutral">Arquivado</x-ui.badge></p>
                    <input type="hidden" name="status" value="archived">
                    <p class="t-small mt-1">Somente um administrador pode reativar este blueprint.</p>
                </div>
            @else
                <x-ui.select label="Status" name="status" :options="$statusOptions" required :value="old('status', isset($blueprint) ? $blueprint->status->value : 'active')" />
            @endif
            <x-ui.textarea label="Observações" name="notes" :rows="3" :value="old('notes', $blueprint->notes ?? null)" />
        </div>
    </x-ui.card>

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <x-ui.button :href="$isEdit ? route('blueprints.show', $blueprint) : route('blueprints.index')" variant="outline">Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit">{{ $isEdit ? 'Salvar alterações' : 'Criar Blueprint' }}</x-ui.button>
    </div>
</div>
