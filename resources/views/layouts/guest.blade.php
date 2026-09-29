<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Publikai') }} — @yield('title', 'Acesso interno')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <p class="text-2xl font-bold tracking-tight">Publikai</p>
                <p class="mt-1 text-sm text-slate-500">Ferramenta interna · Jaguartec Tecnologia</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                @yield('content')
            </div>

            <p class="mt-4 text-center text-xs text-slate-400">Uso exclusivo da equipe interna.</p>
        </div>
    </div>
</body>
</html>
