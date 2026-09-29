{{--
    Page header global (Design System). Hierarquia: breadcrumb → título → descrição → ações.
    Uso:
    <x-ui.page-header title="Dashboard" description="...">
        <x-slot:actions><x-ui.button>...</x-ui.button></x-slot:actions>
    </x-ui.page-header>
--}}
@props([
    'title' => null,
    'description' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    @if (count($breadcrumbs) > 0)
        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="flex flex-wrap items-center gap-1 text-xs text-ink-muted">
                @foreach ($breadcrumbs as $crumb)
                    <li class="flex items-center gap-1">
                        @if (! $loop->first)
                            <span aria-hidden="true">/</span>
                        @endif
                        @if (! empty($crumb['url']) && ! $loop->last)
                            <a href="{{ $crumb['url'] }}" class="hover:text-ink">{{ $crumb['label'] }}</a>
                        @else
                            <span @if($loop->last) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            @if ($title)
                <h1 class="t-page-title">{{ $title }}</h1>
            @endif
            @if ($description)
                <p class="t-body mt-1 max-w-2xl">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
