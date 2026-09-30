@extends('layouts.app')

@section('title', 'Editar Blueprint')
@section('header', 'Blueprints')
@section('content')
<x-ui.page-header
    :title="'Editar: '.$blueprint->name"
    description="Atualize a estrutura reutilizável."
    :breadcrumbs="[['label' => 'Blueprints', 'url' => route('blueprints.index')], ['label' => $blueprint->name, 'url' => route('blueprints.show', $blueprint)], ['label' => 'Editar']]"
/>

<form method="POST" action="{{ route('blueprints.update', $blueprint) }}" novalidate>
    @csrf
    @method('PUT')
    @include('blueprints._form', ['blueprint' => $blueprint])
</form>
@endsection
