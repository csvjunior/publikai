@extends('layouts.app')

@section('title', 'Nova conta')
@section('header', 'Contas')
@section('content')
<x-ui.page-header
    title="Nova conta"
    description="Cadastre o perfil que será utilizado para produzir e distribuir conteúdo."
    :breadcrumbs="[['label' => 'Contas', 'url' => route('social-accounts.index')], ['label' => 'Nova']]"
/>

<form method="POST" action="{{ route('social-accounts.store') }}" novalidate>
    @csrf
    @include('social-accounts._form')
</form>
@endsection
