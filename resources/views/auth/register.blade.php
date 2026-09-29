@extends('layouts.guest')

@section('title', 'Cadastro interno')
@section('content')
<h1 class="t-section-title text-lg">Cadastro interno</h1>
<p class="t-body mt-1">Uso exclusivo da equipe Jaguartec.</p>

<form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" novalidate>
    @csrf

    <x-ui.input label="Nome" name="name" type="text" required autofocus autocomplete="name" />

    <x-ui.input label="E-mail" name="email" type="email" required autocomplete="username" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.input label="Senha" name="password" type="password" required autocomplete="new-password" helper="Mínimo de 8 caracteres." />
        <x-ui.input label="Confirmar senha" name="password_confirmation" type="password" required autocomplete="new-password" />
    </div>

    <x-ui.input label="Código interno" name="registration_code" type="password" autocomplete="off" helper="Solicite o código ao administrador." />

    <x-ui.button variant="primary" size="md" full>Criar conta</x-ui.button>
</form>

<p class="mt-4 text-center text-sm text-ink-secondary">Já tem acesso? <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary-hover">Entrar</a></p>
@endsection
