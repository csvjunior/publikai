{{--
    Textarea global (Design System).
    Uso: <x-ui.textarea label="Descrição" name="description" :rows="4" />
--}}
@props([
    'label' => null,
    'name' => null,
    'value' => null,
    'rows' => 4,
    'required' => false,
    'disabled' => false,
    'helper' => null,
])

@php
$id = $attributes->get('id', $name);
$error = $name ? $errors->first($name) : null;
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

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($error) aria-invalid="true" aria-describedby="{{ $describedBy }}" @elseif($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('id')->merge(['class' => 'mt-1 w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:bg-surface-muted disabled:opacity-70 '.($error ? 'border-danger' : 'border-border')]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($helper && ! $error)
        <p id="{{ $id }}-helper" class="t-small mt-1">{{ $helper }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger" role="alert">{{ $error }}</p>
    @endif
</div>
