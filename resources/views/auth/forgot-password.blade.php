@extends('layouts.guest')

@section('title', 'Recuperar senha')
@section('content')
<h1 class="t-section-title text-lg">Recuperar senha</h1>
<p class="t-body mt-1">Informe seu e-mail para receber o link de redefinição.</p>

@if (session('status'))
    <x-ui.alert variant="success" class="mt-4">{{ session('status') }}</x-ui.alert>
@endif

<form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4" novalidate>
    @csrf

    <x-ui.input label="E-mail" name="email" type="email" required autofocus />

    <x-ui.button variant="primary" size="md" full>Enviar link</x-ui.button>
</form>

<p class="mt-4 text-center text-sm text-ink-secondary"><a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary-hover">Voltar ao login</a></p>
@endsection
