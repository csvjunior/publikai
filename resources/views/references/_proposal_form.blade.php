{{-- Revisão humana da proposta (Sprint 5.2). Recebe $profile e $proposal. Altera só persona_data/avatar_data. --}}
@php
    $p = $proposal->persona_data ?? [];
    $a = $proposal->avatar_data ?? [];
@endphp

<form method="POST" action="{{ route('references.proposals.update', [$profile, $proposal]) }}" id="proposal-review-form" class="space-y-6" novalidate>
    @csrf
    @method('PUT')

    <x-ui.card title="Persona" description="Identidade, comunicação e comportamento.">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input label="Nome" name="persona[name]" type="text" required :value="old('persona.name', $p['name'] ?? null)" />
            </div>
            <x-ui.select label="Idioma" name="persona[language]" :options="config('locale-options.languages')" required :value="old('persona.language', $p['language'] ?? null)" />
            <x-ui.select label="Mercado" name="persona[market]" :options="config('locale-options.markets')" required :value="old('persona.market', $p['market'] ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Público" name="persona[audience]" type="text" :value="old('persona.audience', $p['audience'] ?? null)" />
            </div>
            <x-ui.input label="Personalidade" name="persona[personality]" type="text" required :value="old('persona.personality', $p['personality'] ?? null)" />
            <x-ui.input label="Tom" name="persona[tone]" type="text" required :value="old('persona.tone', $p['tone'] ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Estilo de comunicação" name="persona[communication_style]" type="text" required :value="old('persona.communication_style', $p['communication_style'] ?? null)" />
            </div>
            <x-ui.input label="Vocabulário" name="persona[vocabulary]" type="text" required :value="old('persona.vocabulary', $p['vocabulary'] ?? null)" />
            <x-ui.input label="Estilo de CTA" name="persona[default_cta_style]" type="text" required :value="old('persona.default_cta_style', $p['default_cta_style'] ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.textarea label="Expressões" name="persona[expressions]" :rows="3" required :value="old('persona.expressions', $p['expressions'] ?? null)" />
            </div>
            <div class="sm:col-span-2">
                <x-ui.textarea label="Preferências" name="persona[content_preferences]" :rows="3" required :value="old('persona.content_preferences', $p['content_preferences'] ?? null)" />
            </div>
            <div class="sm:col-span-2">
                <x-ui.textarea label="Evitar" name="persona[avoidances]" :rows="3" required :value="old('persona.avoidances', $p['avoidances'] ?? null)" />
            </div>
            <div class="sm:col-span-2">
                <x-ui.textarea label="Observações" name="persona[notes]" :rows="2" :value="old('persona.notes', $p['notes'] ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Avatar" description="Identidade, aparência, estilo e voz/contexto.">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input label="Nome" name="avatar[name]" type="text" required :value="old('avatar.name', $a['name'] ?? null)" />
            </div>
            <x-ui.input label="Idade aparente" name="avatar[apparent_age]" type="text" :value="old('avatar.apparent_age', $a['apparent_age'] ?? null)" />
            <x-ui.input label="Apresentação de gênero" name="avatar[gender_presentation]" type="text" :value="old('avatar.gender_presentation', $a['gender_presentation'] ?? null)" />
            <x-ui.input label="Cabelo" name="avatar[hair]" type="text" :value="old('avatar.hair', $a['hair'] ?? null)" />
            <x-ui.input label="Olhos" name="avatar[eyes]" type="text" :value="old('avatar.eyes', $a['eyes'] ?? null)" />
            <x-ui.input label="Pele" name="avatar[skin]" type="text" :value="old('avatar.skin', $a['skin'] ?? null)" />
            <x-ui.input label="Descrição étnico-visual" name="avatar[ethnicity_description]" type="text" :value="old('avatar.ethnicity_description', $a['ethnicity_description'] ?? null)" />
            <x-ui.input label="Descrição corporal" name="avatar[body_description]" type="text" :value="old('avatar.body_description', $a['body_description'] ?? null)" />
            <x-ui.input label="Vestuário padrão" name="avatar[default_clothing]" type="text" :value="old('avatar.default_clothing', $a['default_clothing'] ?? null)" />
            <x-ui.input label="Estilo visual" name="avatar[visual_style]" type="text" required :value="old('avatar.visual_style', $a['visual_style'] ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Cenários preferenciais" name="avatar[preferred_scenarios]" type="text" :value="old('avatar.preferred_scenarios', $a['preferred_scenarios'] ?? null)" />
            </div>
            <div class="sm:col-span-2">
                <x-ui.input label="Descrição de voz" name="avatar[voice_description]" type="text" :value="old('avatar.voice_description', $a['voice_description'] ?? null)" />
            </div>
            <x-ui.select label="Idioma" name="avatar[language]" :options="config('locale-options.languages')" required :value="old('avatar.language', $a['language'] ?? null)" />
            <x-ui.select label="Mercado" name="avatar[market]" :options="config('locale-options.markets')" required :value="old('avatar.market', $a['market'] ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.textarea label="Notas de referência visual" name="avatar[reference_notes]" :rows="2" :value="old('avatar.reference_notes', $a['reference_notes'] ?? null)" />
            </div>
            <div class="sm:col-span-2">
                <x-ui.textarea label="Observações" name="avatar[notes]" :rows="2" :value="old('avatar.notes', $a['notes'] ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <x-ui.button variant="primary" type="submit">Salvar revisão</x-ui.button>
    </div>
</form>
