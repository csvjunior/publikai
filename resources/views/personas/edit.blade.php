@extends('layouts.app')

@section('title', 'Editar persona')
@section('header', 'Personas')
@section('content')
<x-ui.page-header
    :title="'Editar: '.$persona->name"
    description="Atualize a identidade de comunicação."
    :breadcrumbs="[['label' => 'Personas', 'url' => route('personas.index')], ['label' => $persona->name, 'url' => route('personas.show', $persona)], ['label' => 'Editar']]"
/>

<form method="POST" action="{{ route('personas.update', $persona) }}" novalidate>
    @csrf
    @method('PUT')
    @include('personas._form', ['persona' => $persona])
</form>
@endsection
