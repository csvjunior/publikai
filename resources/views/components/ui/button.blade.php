{{--
    Botão global do Publikai (Design System).
    Uso: <x-ui.button variant="primary" size="md">Salvar</x-ui.button>
    Variantes: primary | secondary | outline | ghost | danger | ai
    Tamanhos: sm | md | lg
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'submit',
    'href' => null,
    'loading' => false,
    'full' => false,
])

@php
$width = $full ? 'w-full' : 'w-full sm:w-auto';
$base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 '.$width;

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2.5 text-sm',
    'lg' => 'px-5 py-3 text-sm',
];

$variants = [
    'primary' => 'bg-primary text-white hover:bg-primary-hover focus-visible:outline-primary',
    'secondary' => 'bg-ink text-white hover:bg-sidebar-hover focus-visible:outline-ink',
    'outline' => 'border border-border bg-surface text-ink hover:bg-surface-muted focus-visible:outline-primary',
    'ghost' => 'bg-transparent text-ink hover:bg-surface-muted focus-visible:outline-primary',
    'danger' => 'bg-danger text-white hover:bg-danger-hover focus-visible:outline-danger',
    'ai' => 'bg-accent text-white hover:bg-accent-hover focus-visible:outline-accent',
];

$classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
$isDisabled = $loading || $attributes->has('disabled');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if($isDisabled) aria-disabled="true" @endif>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @if($isDisabled) disabled @endif @if($loading) aria-busy="true" @endif {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif
