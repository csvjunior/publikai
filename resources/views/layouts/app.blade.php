<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Publikai') }} — @yield('title', 'Painel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
<div class="min-h-screen lg:flex">
    {{-- Sidebar desktop --}}
    <aside class="hidden w-64 shrink-0 flex-col bg-slate-900 text-slate-200 lg:flex">
        @include('layouts.partials.sidebar')
    </aside>

    {{-- Sidebar mobile --}}
    <div id="mobile-menu" class="fixed inset-0 z-40 hidden lg:hidden">
        <div id="mobile-backdrop" class="absolute inset-0 bg-slate-900/60"></div>
        <aside class="absolute inset-y-0 left-0 flex w-72 flex-col bg-slate-900 text-slate-200 shadow-xl">
            @include('layouts.partials.sidebar')
        </aside>
    </div>

    <div class="flex min-h-screen flex-1 flex-col">
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
            <button id="menu-button" type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">@yield('header', 'Dashboard')</p>
                <p class="truncate text-xs text-slate-500">{{ auth()->user()->name }} · {{ auth()->user()->role->value }}</p>
            </div>
            <div class="ml-auto">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">Sair</button>
                </form>
            </div>
        </header>

        <main class="flex-1 px-4 py-6 sm:px-6">
            @yield('content')
        </main>
    </div>
</div>

<script>
    const menu = document.getElementById('mobile-menu');
    const openBtn = document.getElementById('menu-button');
    const backdrop = document.getElementById('mobile-backdrop');
    if (openBtn && menu) openBtn.addEventListener('click', () => menu.classList.remove('hidden'));
    if (backdrop && menu) backdrop.addEventListener('click', () => menu.classList.add('hidden'));
</script>
</body>
</html>
