{{--
    Badge global (Design System). Uma cor por significado, sempre.
    Variantes: neutral | success | warning | danger | info | ai
--}}
@props(['variant' => 'neutral'])

@php
$variants = [
    'neutral' => 'bg-surface-muted text-ink-secondary ring-border',
    'success' => 'bg-success-soft text-success ring-success-border',
    'warning' => 'bg-warning-soft text-warning ring-warning-border',
    'danger' => 'bg-danger-soft text-danger ring-danger-border',
    'info' => 'bg-info-soft text-info ring-info-border',
    'ai' => 'bg-accent-soft text-accent ring-accent-border',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset '.($variants[$variant] ?? $variants['neutral'])]) }}>{{ $slot }}</span>
