@extends('layouts.app')

@section('title', 'Novo avatar')
@section('header', 'Avatares')
@section('content')
<x-ui.page-header
    title="Novo avatar"
    description="Cadastre a identidade visual para os conteúdos."
    :breadcrumbs="[['label' => 'Avatares', 'url' => route('avatars.index')], ['label' => 'Novo']]"
/>

<form method="POST" action="{{ route('avatars.store') }}" novalidate>
    @csrf
    @include('avatars._form')
</form>
@endsection
