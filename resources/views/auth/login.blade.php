@extends('layouts.guest')

@section('title', 'Entrar')
@section('content')
<h1 class="t-section-title text-lg">Entrar no Publikai</h1>
<p class="t-body mt-1">Acesso restrito à equipe interna.</p>

@if (session('status'))
    <x-ui.alert variant="success" class="mt-4">{{ session('status') }}</x-ui.alert>
@endif

<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" novalidate>
    @csrf

    <x-ui.input label="E-mail" name="email" type="email" required autofocus autocomplete="username" />

    <x-ui.input label="Senha" name="password" type="password" required autocomplete="current-password" />

    <div class="flex items-center justify-between gap-2 text-sm">
        <label class="flex cursor-pointer items-center gap-2 text-ink-secondary">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-border accent-primary">
            Lembrar
        </label>
        <a href="{{ route('password.request') }}" class="font-medium text-primary hover:text-primary-hover">Esqueci a senha</a>
    </div>

    <x-ui.button variant="primary" size="md" full>Entrar</x-ui.button>
</form>

@if (config('registration.enabled'))
    <p class="mt-4 text-center text-sm text-ink-secondary">Sem acesso? <a href="{{ route('register') }}" class="font-medium text-primary hover:text-primary-hover">Criar conta interna</a></p>
@endif
@endsection
