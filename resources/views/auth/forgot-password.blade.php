@extends('layouts.guest')

@section('title', 'Recuperar senha')
@section('content')
<h1 class="text-lg font-semibold">Recuperar senha</h1>
<p class="mt-1 text-sm text-slate-500">Informe seu e-mail para receber o link de redefinição.</p>

@if (session('status'))
    <div class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 ring-1 ring-emerald-200">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label for="email" class="block text-sm font-medium">E-mail</label>
        <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="w-full rounded-lg bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Enviar link</button>
</form>

<p class="mt-4 text-center text-sm text-slate-500"><a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Voltar ao login</a></p>
@endsection
