{{-- Proposta de identidade em destaque (Sprint 5.2). Recebe $proposal. Sem JSON cru. --}}
@php
    $persona = $proposal->persona_data ?? [];
    $avatar = $proposal->avatar_data ?? [];
    $rationale = $proposal->rationale ?? [];
@endphp

<div class="flex flex-wrap items-center gap-2">
    <x-ui.badge :variant="$proposal->status->badgeVariant()">{{ $proposal->status->label() }}</x-ui.badge>
    <span class="t-small">{{ $proposal->created_at?->display() }} · {{ $proposal->provider ?? '—' }} / {{ $proposal->model ?? '—' }}</span>
</div>

@if ($proposal->status->value === 'applied')
    <p class="t-body mt-3">Aplicada em {{ $proposal->applied_at?->display() }}.</p>
    <div class="mt-2 flex flex-wrap gap-2">
        @if ($proposal->appliedPersona)
            <x-ui.button :href="route('personas.show', $proposal->appliedPersona)" variant="outline" size="sm">Ver Persona</x-ui.button>
        @endif
        @if ($proposal->appliedAvatar)
            <x-ui.button :href="route('avatars.show', $proposal->appliedAvatar)" variant="outline" size="sm">Ver Avatar</x-ui.button>
        @endif
    </div>
@endif

@if ($proposal->status->value === 'failed')
    <p class="t-card-title mt-3">Falha na proposta</p>
    <p class="t-body mt-1">Não foi possível gerar a proposta agora. Tente novamente mais tarde.</p>
@endif

@if (! empty($persona) || ! empty($avatar))
    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-border p-4">
            <h4 class="t-section-title">Persona sugerida</h4>
            <dl class="mt-3 space-y-2 text-sm">
                @foreach (['Nome' => $persona['name'] ?? null, 'Idioma/Mercado' => isset($persona['language']) ? $persona['language'].' · '.($persona['market'] ?? '') : null, 'Público' => $persona['audience'] ?? null, 'Personalidade' => $persona['personality'] ?? null, 'Tom' => $persona['tone'] ?? null, 'Estilo de comunicação' => $persona['communication_style'] ?? null, 'Vocabulário' => $persona['vocabulary'] ?? null, 'CTA padrão' => $persona['default_cta_style'] ?? null] as $label => $value)
                    @if ($value)
                        <div><dt class="t-small font-medium uppercase tracking-wide">{{ $label }}</dt><dd class="t-body mt-0.5">{{ $value }}</dd></div>
                    @endif
                @endforeach
            </dl>
            @if (! empty($persona['expressions']))
                <div class="mt-2"><p class="t-small font-medium uppercase tracking-wide">Expressões</p><p class="t-body mt-0.5 whitespace-pre-line">{{ $persona['expressions'] }}</p></div>
            @endif
            @if (! empty($persona['content_preferences']))
                <div class="mt-2"><p class="t-small font-medium uppercase tracking-wide">Preferências</p><p class="t-body mt-0.5 whitespace-pre-line">{{ $persona['content_preferences'] }}</p></div>
            @endif
            @if (! empty($persona['avoidances']))
                <div class="mt-2"><p class="t-small font-medium uppercase tracking-wide">Evitar</p><p class="t-body mt-0.5 whitespace-pre-line">{{ $persona['avoidances'] }}</p></div>
            @endif
        </div>

        <div class="rounded-lg border border-border p-4">
            <h4 class="t-section-title">Avatar sugerido</h4>
            <dl class="mt-3 space-y-2 text-sm">
                @foreach (['Nome' => $avatar['name'] ?? null, 'Idade aparente' => $avatar['apparent_age'] ?? null, 'Apresentação de gênero' => $avatar['gender_presentation'] ?? null, 'Cabelo' => $avatar['hair'] ?? null, 'Olhos' => $avatar['eyes'] ?? null, 'Pele' => $avatar['skin'] ?? null, 'Roupa padrão' => $avatar['default_clothing'] ?? null, 'Estilo visual' => $avatar['visual_style'] ?? null, 'Voz' => $avatar['voice_description'] ?? null, 'Idioma/Mercado' => isset($avatar['language']) ? $avatar['language'].' · '.($avatar['market'] ?? '') : null] as $label => $value)
                    @if ($value)
                        <div><dt class="t-small font-medium uppercase tracking-wide">{{ $label }}</dt><dd class="t-body mt-0.5">{{ $value }}</dd></div>
                    @endif
                @endforeach
            </dl>
            @if (! empty($avatar['body_description']))
                <div class="mt-2"><p class="t-small font-medium uppercase tracking-wide">Descrição corporal</p><p class="t-body mt-0.5">{{ $avatar['body_description'] }}</p></div>
            @endif
            @if (! empty($avatar['preferred_scenarios']))
                <div class="mt-2"><p class="t-small font-medium uppercase tracking-wide">Cenários</p><p class="t-body mt-0.5">{{ $avatar['preferred_scenarios'] }}</p></div>
            @endif
            @if (! empty($avatar['ethnicity_description']))
                <div class="mt-2"><p class="t-small font-medium uppercase tracking-wide">Descrição étnico-visual</p><p class="t-body mt-0.5">{{ $avatar['ethnicity_description'] }}</p></div>
            @endif
        </div>
    </div>

    @if (! empty($rationale['persona']) || ! empty($rationale['avatar']))
        <div class="mt-4 rounded-lg bg-surface-muted p-4">
            <h4 class="t-section-title">Por que a IA sugeriu isso?</h4>
            <div class="mt-2 grid gap-4 sm:grid-cols-2">
                @if (! empty($rationale['persona']))
                    <div>
                        <p class="t-small font-medium uppercase tracking-wide">Persona</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-ink-secondary">
                            @foreach ($rationale['persona'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (! empty($rationale['avatar']))
                    <div>
                        <p class="t-small font-medium uppercase tracking-wide">Avatar</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-ink-secondary">
                            @foreach ($rationale['avatar'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endif
