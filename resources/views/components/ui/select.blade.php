{{--
    Select global (Design System).
    Uso: <x-ui.select label="Papel" name="role" :options="['admin' => 'Admin', 'operator' => 'Operador']" />
--}}
@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'helper' => null,
])

@php
$id = $attributes->get('id', $name);
$error = $name ? $errors->first($name) : null;
$current = old($name, $value);
$describedBy = $error ? $id.'-error' : ($helper ? $id.'-helper' : null);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="t-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($error) aria-invalid="true" aria-describedby="{{ $describedBy }}" @elseif($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('id')->merge(['class' => 'mt-1 w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink focus:border-primary focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:bg-surface-muted disabled:opacity-70 '.($error ? 'border-danger' : 'border-border')]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>

    @if ($helper && ! $error)
        <p id="{{ $id }}-helper" class="t-small mt-1">{{ $helper }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger" role="alert">{{ $error }}</p>
    @endif
</div>
