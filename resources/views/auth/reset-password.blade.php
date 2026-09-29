@extends('layouts.guest')

@section('title', 'Redefinir senha')
@section('content')
<h1 class="t-section-title text-lg">Redefinir senha</h1>
<p class="t-body mt-1">Crie uma nova senha de acesso.</p>

<form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <x-ui.input label="E-mail" name="email" type="email" required autofocus autocomplete="username" :value="$email" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.input label="Nova senha" name="password" type="password" required autocomplete="new-password" helper="Mínimo de 8 caracteres." />
        <x-ui.input label="Confirmar senha" name="password_confirmation" type="password" required autocomplete="new-password" />
    </div>

    <x-ui.button variant="primary" size="md" full>Redefinir senha</x-ui.button>
</form>
@endsection
