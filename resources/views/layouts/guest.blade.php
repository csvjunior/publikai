<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Publikai') }} — @yield('title', 'Acesso interno')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-primary text-base font-bold text-white" aria-hidden="true">P</div>
                <p class="mt-3 text-2xl font-bold tracking-tight text-ink">Publikai</p>
                <p class="t-small mt-1">Ferramenta interna · Jaguartec Tecnologia</p>
            </div>

            <x-ui.card>
                @yield('content')
            </x-ui.card>

            <p class="t-muted mt-4 text-center">Uso exclusivo da equipe interna.</p>
        </div>
    </div>
</body>
</html>
