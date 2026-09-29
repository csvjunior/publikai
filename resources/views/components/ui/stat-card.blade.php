{{--
    Stat card global (Design System). Para indicadores; nunca com dados fabricados.
    Uso: <x-ui.stat-card title="Conteúdos" value="—"><x-ui.badge>Ainda sem dados</x-ui.badge></x-ui.stat-card>
--}}
@props([
    'title' => null,
    'value' => null,
    'hint' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-card border border-border bg-surface p-5 shadow-card']) }}>
    @if ($title)
        <h2 class="t-card-title">{{ $title }}</h2>
    @endif

    @if (! is_null($value))
        <p class="mt-1 text-2xl font-bold tracking-tight text-ink">{{ $value }}</p>
    @endif

    @if ($hint)
        <p class="t-body mt-1">{{ $hint }}</p>
    @endif

    @if (trim($slot) !== '')
        <div class="mt-3">{{ $slot }}</div>
    @endif
</section>
