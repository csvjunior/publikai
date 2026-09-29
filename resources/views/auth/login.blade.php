@extends('layouts.guest')

@section('title', 'Entrar')
@section('content')
<h1 class="text-lg font-semibold">Entrar no Publikai</h1>
<p class="mt-1 text-sm text-slate-500">Acesso restrito à equipe interna.</p>

@if (session('status'))
    <div class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 ring-1 ring-emerald-200">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium">E-mail</label>
        <input id="email" name="email" type="email" required autofocus autocomplete="username" value="{{ old('email') }}"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium">Senha</label>
        <input id="password" name="password" type="password" required autocomplete="current-password"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center justify-between text-sm">
        <label class="flex items-center gap-2 text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300"> Lembrar
        </label>
        <a href="{{ route('password.request') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Esqueci a senha</a>
    </div>

    <button type="submit" class="w-full rounded-lg bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Entrar</button>
</form>

@if (config('registration.enabled'))
    <p class="mt-4 text-center text-sm text-slate-500">Sem acesso? <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Criar conta interna</a></p>
@endif
@endsection
