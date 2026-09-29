{{--
    Card global (Design System). Superfície funcional sobre o canvas.
    Uso: <x-ui.card title="Título" description="...">conteúdo</x-ui.card>
--}}
@props([
    'title' => null,
    'description' => null,
    'padding' => 'md',
])

@php
$paddings = [
    'sm' => 'p-4',
    'md' => 'p-5 sm:p-6',
];
@endphp

<section {{ $attributes->merge(['class' => 'rounded-card border border-border bg-surface shadow-card '.($paddings[$padding] ?? $paddings['md'])]) }}>
    @if ($title || $description || isset($header))
        <header class="mb-4">
            @isset($header)
                {{ $header }}
            @else
                @if ($title)
                    <h2 class="t-card-title">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="t-body mt-1">{{ $description }}</p>
                @endif
            @endisset
        </header>
    @endif

    {{ $slot }}

    @isset($footer)
        <footer class="mt-4 border-t border-border pt-4">
            {{ $footer }}
        </footer>
    @endisset
</section>
