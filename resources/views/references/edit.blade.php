@extends('layouts.app')

@section('title', 'Editar referência')
@section('header', 'Referências')
@section('content')
<x-ui.page-header
    :title="'Editar: '.$profile->name"
    description="Atualize os dados do perfil de referência."
    :breadcrumbs="[['label' => 'Referências', 'url' => route('references.index')], ['label' => $profile->name, 'url' => route('references.show', $profile)], ['label' => 'Editar']]"
/>

<form method="POST" action="{{ route('references.update', $profile) }}" novalidate>
    @csrf
    @method('PUT')
    @include('references._form', ['profile' => $profile])
</form>
@endsection
