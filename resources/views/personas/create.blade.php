@extends('layouts.app')

@section('title', 'Nova persona')
@section('header', 'Personas')
@section('content')
<x-ui.page-header
    title="Nova persona"
    description="Crie uma identidade de comunicação para os conteúdos."
    :breadcrumbs="[['label' => 'Personas', 'url' => route('personas.index')], ['label' => 'Nova']]"
/>

<form method="POST" action="{{ route('personas.store') }}" novalidate>
    @csrf
    @include('personas._form')
</form>
@endsection
