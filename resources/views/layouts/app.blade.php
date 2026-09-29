<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Publikai') }} — @yield('title', 'Painel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="min-h-screen lg:flex">
    {{-- Sidebar desktop (248px, persistente) --}}
    <aside class="hidden w-62 shrink-0 flex-col bg-sidebar text-sidebar-ink lg:flex" aria-label="Navegação principal">
        @include('layouts.partials.sidebar', ['drawer' => false])
    </aside>

    {{-- Drawer mobile/tablet --}}
    <div id="mobile-menu" class="fixed inset-0 z-40 hidden lg:hidden">
        <div id="mobile-backdrop" class="absolute inset-0 bg-ink/60" aria-hidden="true"></div>
        <aside role="dialog" aria-modal="true" aria-label="Navegação principal" class="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col bg-sidebar text-sidebar-ink shadow-xl">
            @include('layouts.partials.sidebar', ['drawer' => true])
        </aside>
    </div>

    <div class="flex min-h-screen min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 border-b border-border bg-surface">
            <div class="pk-workspace flex items-center gap-3 py-3">
                <button id="menu-button" type="button" aria-expanded="false" aria-controls="mobile-menu"
                    class="rounded-lg p-2 text-ink-secondary hover:bg-surface-muted lg:hidden" aria-label="Abrir menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ink">@yield('header', 'Dashboard')</p>
                    <p class="truncate text-xs text-ink-muted">{{ auth()->user()->name }} · {{ auth()->user()->role->value }}</p>
                </div>
            </div>
        </header>

        <main class="flex-1 py-6">
            <div class="pk-workspace">
                @yield('content')
            </div>
        </main>
    </div>
</div>
</body>
</html>
