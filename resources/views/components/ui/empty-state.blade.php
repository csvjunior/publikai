{{--
    Empty state global (Design System). Informa o que não existe, por que é útil e a próxima ação.
    Uso: <x-ui.empty-state title="..." description="..." action-label="..." action-href="..." />
--}}
@props([
    'title' => null,
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-4 py-8 text-center']) }}>
    @isset($icon)
        <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-surface-muted text-ink-muted" aria-hidden="true">
            {{ $icon }}
        </div>
    @else
        <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-surface-muted text-ink-muted" aria-hidden="true">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.75 7.5L12 3m0 0l8.25 4.5M12 3v7.5" />
            </svg>
        </div>
    @endisset

    @if ($title)
        <p class="t-card-title">{{ $title }}</p>
    @endif

    @if ($description)
        <p class="t-body mt-1 max-w-sm">{{ $description }}</p>
    @endif

    @if ($actionLabel && $actionHref)
        <div class="mt-4">
            <x-ui.button variant="primary" size="sm" :href="$actionHref">{{ $actionLabel }}</x-ui.button>
        </div>
    @endif

    {{ $slot }}
</div>
