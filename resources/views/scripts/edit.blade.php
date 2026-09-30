@extends('layouts.app')

@section('title', 'Editar roteiro')
@section('header', 'Roteiros')
@section('content')
<x-ui.page-header
    :title="'Editar: '.$script->title"
    description="Revise o texto do roteiro."
    :breadcrumbs="[['label' => 'Roteiros', 'url' => route('scripts.index')], ['label' => $script->title, 'url' => route('scripts.show', $script)], ['label' => 'Editar']]"
/>

@if ($editable)
    <form method="POST" action="{{ route('scripts.update', $script) }}" novalidate>
        @csrf
        @method('PUT')
        @include('scripts._form', ['script' => $script])
    </form>
@else
    <x-ui.card>
        <x-ui.empty-state
            title="Roteiro não editável"
            description="Roteiros aprovados, arquivados ou com falha de geração não podem ser editados. Para corrigir uma falha, crie um novo roteiro."
        />
    </x-ui.card>
@endif
@endsection
