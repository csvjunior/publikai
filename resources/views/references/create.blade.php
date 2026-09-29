@extends('layouts.app')

@section('title', 'Nova referência')
@section('header', 'Referências')
@section('content')
<x-ui.page-header
    title="Nova referência"
    description="Cadastre o perfil que produz conteúdos relevantes para o seu nicho."
    :breadcrumbs="[['label' => 'Referências', 'url' => route('references.index')], ['label' => 'Nova']]"
/>

<form method="POST" action="{{ route('references.store') }}" novalidate>
    @csrf
    @include('references._form')
</form>
@endsection
