@extends('layouts.guest')

@section('title', 'Cadastro interno')
@section('content')
<h1 class="text-lg font-semibold">Cadastro interno</h1>
<p class="mt-1 text-sm text-slate-500">Uso exclusivo da equipe Jaguartec.</p>

<form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
    @csrf

    <div>
        <label for="name" class="block text-sm font-medium">Nome</label>
        <input id="name" name="name" type="text" required autofocus autocomplete="name" value="{{ old('name') }}"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium">E-mail</label>
        <input id="email" name="email" type="email" required autocomplete="username" value="{{ old('email') }}"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="block text-sm font-medium">Senha</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Confirmar senha</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
    </div>

    <div>
        <label for="registration_code" class="block text-sm font-medium">Código interno</label>
        <input id="registration_code" name="registration_code" type="password" autocomplete="off" placeholder="Solicite ao administrador"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        @error('registration_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="w-full rounded-lg bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Criar conta</button>
</form>

<p class="mt-4 text-center text-sm text-slate-500">Já tem acesso? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Entrar</a></p>
@endsection
