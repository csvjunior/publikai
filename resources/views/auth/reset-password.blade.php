@extends('layouts.guest')

@section('title', 'Redefinir senha')
@section('content')
<h1 class="text-lg font-semibold">Redefinir senha</h1>
<p class="mt-1 text-sm text-slate-500">Crie uma nova senha de acesso.</p>

<form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div>
        <label for="email" class="block text-sm font-medium">E-mail</label>
        <input id="email" name="email" type="email" required autofocus autocomplete="username" value="{{ old('email', $email) }}"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="block text-sm font-medium">Nova senha</label>
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

    <button type="submit" class="w-full rounded-lg bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Redefinir senha</button>
</form>
@endsection
