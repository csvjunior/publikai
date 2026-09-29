@extends('layouts.app')

@section('title', 'Novo produto')
@section('header', 'Produtos')
@section('content')
<x-ui.page-header
    title="Novo produto"
    description="Cadastre o produto que será utilizado nas operações de conteúdo e afiliados."
    :breadcrumbs="[['label' => 'Produtos', 'url' => route('products.index')], ['label' => 'Novo']]"
/>

<form method="POST" action="{{ route('products.store') }}" novalidate>
    @csrf
    @include('products._form')
</form>
@endsection
