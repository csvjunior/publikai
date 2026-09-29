@extends('layouts.app')

@section('title', 'Editar produto')
@section('header', 'Produtos')
@section('content')
<x-ui.page-header
    :title="'Editar: '.$product->name"
    description="Atualize os dados do produto. O arquivamento é feito alterando o status."
    :breadcrumbs="[['label' => 'Produtos', 'url' => route('products.index')], ['label' => $product->name, 'url' => route('products.show', $product)], ['label' => 'Editar']]"
/>

<form method="POST" action="{{ route('products.update', $product) }}" novalidate>
    @csrf
    @method('PUT')
    @include('products._form', ['product' => $product])
</form>
@endsection
