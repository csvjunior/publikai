{{--
    Campo de texto global (Design System).
    Uso: <x-ui.input label="E-mail" name="email" type="email" required />
    Erro resolvido automaticamente via $errors. Nunca use placeholder no lugar do label.
--}}
@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'disabled' => false,
    'helper' => null,
    'autocomplete' => null,
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

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if(! is_null($value)) value="{{ old($name, $value) }}" @else value="{{ old($name) }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($error) aria-invalid="true" aria-describedby="{{ $describedBy }}" @elseif($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except(['id', 'class'])->merge(['class' => 'mt-1 w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:bg-surface-muted disabled:opacity-70 '.($error ? 'border-danger' : 'border-border')]) }}
    >

    @if ($helper && ! $error)
        <p id="{{ $id }}-helper" class="t-small mt-1">{{ $helper }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger" role="alert">{{ $error }}</p>
    @endif
</div>
