@extends('layouts.app')

@section('title', 'Contas')
@section('header', 'Contas')
@section('content')
<x-ui.page-header
    title="Contas"
    description="Perfis sociais gerenciados pelo Publikai."
>
    <x-slot:actions>
        <x-ui.button :href="route('social-accounts.create')" variant="primary">Nova conta</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if ($accounts->isEmpty())
    <x-ui.card>
        <x-ui.empty-state
            title="Nenhuma conta cadastrada"
            description="Cadastre os perfis que serão utilizados para produzir e distribuir conteúdo."
            action-label="Cadastrar conta"
            :action-href="route('social-accounts.create')"
        />
    </x-ui.card>
@else
    <x-ui.table :headers="['Conta', 'Plataforma', 'Mercado', 'Idioma', 'Nicho', 'Status', 'Atualização', 'Ações']">
        <tbody>
            @foreach ($accounts as $account)
                <tr>
                    <td>
                        <a href="{{ route('social-accounts.show', $account) }}" class="font-medium text-primary hover:text-primary-hover">{{ $account->name }}</a>
                        <p class="t-small mt-0.5">{{ '@'.$account->username }}</p>
                    </td>
                    <td><x-ui.badge :variant="$account->platform->badgeVariant()">{{ $account->platform->label() }}</x-ui.badge></td>
                    <td>{{ $account->market ?? '—' }}</td>
                    <td>{{ $account->language ?? '—' }}</td>
                    <td>{{ $account->niche ?? '—' }}</td>
                    <td><x-ui.badge :variant="$account->status->badgeVariant()">{{ $account->status->label() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap">{{ $account->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('social-accounts.show', $account) }}" class="font-medium text-primary hover:text-primary-hover">Ver</a>
                        <span class="mx-1 text-border" aria-hidden="true">·</span>
                        <a href="{{ route('social-accounts.edit', $account) }}" class="font-medium text-primary hover:text-primary-hover">Editar</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>

    @if ($accounts->hasPages())
        <div class="mt-4">{{ $accounts->links() }}</div>
    @endif
@endif
@endsection
