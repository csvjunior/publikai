@extends('layouts.app')

@section('title', 'Editar conta')
@section('header', 'Contas')
@section('content')
<x-ui.page-header
    :title="'Editar: '.$account->name"
    description="Atualize os dados operacionais da conta."
    :breadcrumbs="[['label' => 'Contas', 'url' => route('social-accounts.index')], ['label' => $account->name, 'url' => route('social-accounts.show', $account)], ['label' => 'Editar']]"
/>

<form method="POST" action="{{ route('social-accounts.update', $account) }}" novalidate>
    @csrf
    @method('PUT')
    @include('social-accounts._form', ['account' => $account])
</form>
@endsection
