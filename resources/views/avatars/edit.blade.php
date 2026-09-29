@extends('layouts.app')

@section('title', 'Editar avatar')
@section('header', 'Avatares')
@section('content')
<x-ui.page-header
    :title="'Editar: '.$avatar->name"
    description="Atualize a identidade visual."
    :breadcrumbs="[['label' => 'Avatares', 'url' => route('avatars.index')], ['label' => $avatar->name, 'url' => route('avatars.show', $avatar)], ['label' => 'Editar']]"
/>

<form method="POST" action="{{ route('avatars.update', $avatar) }}" novalidate>
    @csrf
    @method('PUT')
    @include('avatars._form', ['avatar' => $avatar])
</form>
@endsection
