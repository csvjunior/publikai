{{--
    Alerta global (Design System). Mensagens claras, sem detalhes técnicos internos.
    Variantes: info | success | warning | danger
--}}
@props([
    'variant' => 'info',
    'title' => null,
])

@php
$variants = [
    'info' => 'border-info-border bg-info-soft text-info',
    'success' => 'border-success-border bg-success-soft text-success',
    'warning' => 'border-warning-border bg-warning-soft text-warning',
    'danger' => 'border-danger-border bg-danger-soft text-danger',
];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'rounded-lg border px-3 py-2 text-sm '.($variants[$variant] ?? $variants['info'])]) }}>
    @if ($title)
        <p class="font-semibold">{{ $title }}</p>
    @endif
    {{ $slot }}
</div>
