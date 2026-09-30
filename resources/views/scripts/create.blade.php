@extends('layouts.app')

@section('title', 'Novo roteiro')
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    title="Novo roteiro"
    description="Selecione o contexto e escolha o modo: manual ou geração por IA."
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => 'Novo']]"
/>

@if (session('script_notice'))
    <x-ui.alert variant="warning" class="mb-6">{{ session('script_notice') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Modo manual" description="Preencha o roteiro com seu próprio texto.">
        <form method="POST" action="{{ route('scripts.store') }}" novalidate>
            @csrf
            @include('scripts._form')
        </form>
    </x-ui.card>

    <x-ui.card title="Gerar com IA" description="Gera o roteiro a partir do contexto usando o provider configurado. Exige clique explícito.">
        <form method="POST" action="{{ route('scripts.generate') }}" data-once novalidate>
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select label="Produto" name="product_id" :options="$productOptions ?? []" required :value="old('product_id')" />
                <x-ui.select label="Blueprint" name="content_blueprint_id" :options="$blueprintOptions ?? []" required :value="old('content_blueprint_id')" />
                <x-ui.select label="Persona" name="persona_id" :options="$personaOptions ?? []" required :value="old('persona_id')" />
                <x-ui.select label="Avatar" name="avatar_id" :options="$avatarOptions ?? []" required :value="old('avatar_id')" />
            </div>
            <div class="mt-4">
                <x-ui.button variant="ai" type="submit">Gerar roteiro</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
