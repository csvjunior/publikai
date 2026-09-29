@extends('layouts.app')

@section('title', 'Produtos')
@section('header', 'Produtos')
@section('content')
<x-ui.page-header
    title="Produtos"
    description="Produtos e ofertas utilizados nas operações de conteúdo e afiliados."
>
    <x-slot:actions>
        <x-ui.button :href="route('products.create')" variant="primary">Novo produto</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

@if ($products->isEmpty())
    <x-ui.card>
        <x-ui.empty-state
            title="Nenhum produto cadastrado"
            description="Cadastre o primeiro produto que será utilizado nas suas operações de conteúdo e afiliados."
            action-label="Cadastrar produto"
            :action-href="route('products.create')"
        />
    </x-ui.card>
@else
    <x-ui.table :headers="['Produto', 'Mercado', 'Idioma', 'Preço', 'Rede', 'Status', 'Atualização', 'Ações']">
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>
                        <a href="{{ route('products.show', $product) }}" class="font-medium text-primary hover:text-primary-hover">{{ $product->name }}</a>
                        @if ($product->category)
                            <p class="t-small mt-0.5">{{ $product->category }}</p>
                        @endif
                    </td>
                    <td>{{ $product->market ?? '—' }}</td>
                    <td>{{ $product->language ?? '—' }}</td>
                    <td class="whitespace-nowrap">
                        @if (! is_null($product->price))
                            {{ $product->currency }} {{ number_format($product->price, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $product->affiliate_network ?? '—' }}</td>
                    <td><x-ui.badge :variant="$product->status->badgeVariant()">{{ $product->status->label() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap">{{ $product->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('products.show', $product) }}" class="font-medium text-primary hover:text-primary-hover">Ver</a>
                        <span class="mx-1 text-border" aria-hidden="true">·</span>
                        <a href="{{ route('products.edit', $product) }}" class="font-medium text-primary hover:text-primary-hover">Editar</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>

    @if ($products->hasPages())
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
@endif
@endsection
