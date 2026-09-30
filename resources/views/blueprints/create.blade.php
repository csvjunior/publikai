@extends('layouts.app')

@section('title', 'Novo Blueprint')
@section('header', 'Blueprints')
@section('content')
<x-ui.page-header
    title="Novo Blueprint"
    description="Crie uma estrutura reutilizável para orientar roteiros."
    :breadcrumbs="[['label' => 'Blueprints', 'url' => route('blueprints.index')], ['label' => 'Novo']]"
/>

<form method="POST" action="{{ route('blueprints.store') }}" novalidate>
    @csrf
    @include('blueprints._form')
</form>
@endsection
