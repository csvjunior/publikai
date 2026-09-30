{{-- Formulário de roteiro (create manual/edit). Contexto travado na edição via hidden inputs. --}}
@php
    $isEdit = isset($script) && $script->exists;
    $statusLocked = $isEdit && $script->isArchived()
        && \Illuminate\Support\Facades\Gate::denies('updateStatus', [$script, \App\Enums\ContentScriptStatus::Active->value]);

    $statusOptions = [];
    if (! $statusLocked) {
        foreach (\App\Enums\ContentScriptStatus::cases() as $case) {
            if (in_array($case->value, ['draft', 'ready', 'approved', 'archived'], true)
                && \Illuminate\Support\Facades\Gate::allows('updateStatus', [$script ?? new \App\Models\ContentScript, $case->value])) {
                $statusOptions[$case->value] = $case->label();
            }
        }
    }
@endphp

<div class="space-y-6">
    @if (! $isEdit)
        <x-ui.card title="Contexto" description="Produto, estrutura e identidades do roteiro.">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select label="Produto" name="product_id" :options="$productOptions ?? []" required :value="old('product_id')" />
                <x-ui.select label="Blueprint" name="content_blueprint_id" :options="$blueprintOptions ?? []" required :value="old('content_blueprint_id')" />
                <x-ui.select label="Persona" name="persona_id" :options="$personaOptions ?? []" required :value="old('persona_id')" />
                <x-ui.select label="Avatar" name="avatar_id" :options="$avatarOptions ?? []" required :value="old('avatar_id')" />
            </div>
        </x-ui.card>
    @else
        <input type="hidden" name="product_id" value="{{ old('product_id', $script->product_id) }}">
        <input type="hidden" name="content_blueprint_id" value="{{ old('content_blueprint_id', $script->content_blueprint_id) }}">
        <input type="hidden" name="persona_id" value="{{ old('persona_id', $script->persona_id) }}">
        <input type="hidden" name="avatar_id" value="{{ old('avatar_id', $script->avatar_id) }}">
    @endif

    <x-ui.card title="Roteiro" description="Texto estruturado do conteúdo.">
        <div class="space-y-4">
            <x-ui.input label="Título" name="title" type="text" required autofocus :value="old('title', $script->title ?? null)" />
            <x-ui.input label="Objetivo" name="objective" type="text" :value="old('objective', $script->objective ?? null)" />
            <x-ui.textarea label="Hook" name="hook" :rows="2" required :value="old('hook', $script->hook ?? null)" />
            <x-ui.textarea label="Abertura" name="opening" :rows="3" :value="old('opening', $script->opening ?? null)" />
            <x-ui.textarea label="Corpo" name="body" :rows="6" required :value="old('body', $script->body ?? null)" />
            <x-ui.textarea label="CTA" name="cta" :rows="2" required :value="old('cta', $script->cta ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Produção" description="Direções para a futura Video Factory.">
        <div class="space-y-4">
            <x-ui.textarea label="Texto na tela" name="on_screen_text" :rows="2" :value="old('on_screen_text', $script->on_screen_text ?? null)" />
            <x-ui.textarea label="Direção visual" name="visual_direction" :rows="3" :value="old('visual_direction', $script->visual_direction ?? null)" />
            <x-ui.textarea label="Direção de voz" name="voice_direction" :rows="3" :value="old('voice_direction', $script->voice_direction ?? null)" />
            <x-ui.input label="Duração (segundos)" name="duration_seconds" type="number" min="1" :value="old('duration_seconds', $script->duration_seconds ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Controle" description="Status e observações internas.">
        <div class="space-y-4">
            @if ($statusLocked)
                <div>
                    <p class="t-label">Status</p>
                    <p class="mt-1"><x-ui.badge variant="neutral">Arquivado</x-ui.badge></p>
                    <input type="hidden" name="status" value="archived">
                    <p class="t-small mt-1">Somente um administrador pode reativar este roteiro.</p>
                </div>
            @else
                <x-ui.select label="Status" name="status" :options="$statusOptions" required :value="old('status', isset($script) ? $script->status->value : 'draft')" />
            @endif
            <x-ui.textarea label="Observações" name="notes" :rows="3" :value="old('notes', $script->notes ?? null)" />
        </div>
    </x-ui.card>

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <x-ui.button :href="$isEdit ? route('scripts.show', $script) : route('scripts.index')" variant="outline">Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit">{{ $isEdit ? 'Salvar alterações' : 'Criar rascunho' }}</x-ui.button>
    </div>
</div>
