@extends('layouts.app')

@section('title', $product->name)
@section('header', 'Produtos')
@section('content')
<x-ui.page-header
    :title="$product->name"
    :description="$product->category"
    :breadcrumbs="[['label' => 'Produtos', 'url' => route('products.index')], ['label' => $product->name]]"
>
    <x-slot:actions>
        <x-ui.button :href="route('products.edit', $product)" variant="outline">Editar</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if (session('status'))
    <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
@endif

<div class="space-y-6">
    <x-ui.card title="Detalhes do produto">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :variant="$product->status->badgeVariant()">{{ $product->status->label() }}</x-ui.badge>
            @if ($product->market)
                <x-ui.badge variant="info">{{ $product->market }}</x-ui.badge>
            @endif
            @if ($product->language)
                <x-ui.badge variant="neutral">{{ config('locale-options.languages')[$product->language] ?? $product->language }}</x-ui.badge>
            @endif
        </div>

        <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Preço</dt>
                <dd class="t-body mt-0.5 font-medium text-ink">
                    @if (! is_null($product->price))
                        {{ $product->currency }} {{ number_format($product->price, 2, ',', '.') }}
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Categoria</dt>
                <dd class="t-body mt-0.5">{{ $product->category ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Rede de afiliados</dt>
                <dd class="t-body mt-0.5">{{ $product->affiliate_network ?? '—' }}</dd>
            </div>
            <div>
                <dt class="t-small font-medium uppercase tracking-wide">Comissão</dt>
                <dd class="t-body mt-0.5">
                    @if (! is_null($product->commission_value))
                        {{ config('products.commission_types')[$product->commission_type] ?? $product->commission_type }}:
                        {{ $product->commission_type === 'percent' ? number_format($product->commission_value, 2, ',', '.').'%' : ($product->currency.' '.number_format($product->commission_value, 2, ',', '.')) }}
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="t-small font-medium uppercase tracking-wide">URL original</dt>
                <dd class="t-body mt-0.5 min-w-0">
                    @if ($product->product_url)
                        <span class="block truncate" title="{{ $product->product_url }}">{{ $product->product_url }}</span>
                        <a href="{{ $product->product_url }}" target="_blank" rel="noopener" class="font-medium text-primary hover:text-primary-hover">Abrir link</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            @if ($product->description)
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Descrição</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $product->description }}</dd>
                </div>
            @endif
            @if ($product->notes)
                <div class="sm:col-span-2">
                    <dt class="t-small font-medium uppercase tracking-wide">Observações</dt>
                    <dd class="t-body mt-0.5 whitespace-pre-line">{{ $product->notes }}</dd>
                </div>
            @endif
        </dl>
    </x-ui.card>

    <x-ui.card title="Links de afiliado" description="Um produto pode ter vários links; apenas um é o principal.">
        @if ($product->affiliateLinks->isEmpty())
            <x-ui.empty-state
                title="Nenhum link ainda"
                description="Adicione o primeiro link de afiliado deste produto no formulário abaixo."
            />
        @else
            <ul class="space-y-4">
                @foreach ($product->affiliateLinks as $link)
                    <li class="rounded-lg border border-border p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="t-card-title">{{ $link->label }}</p>
                            @if ($link->is_primary)
                                <x-ui.badge variant="ai">Principal</x-ui.badge>
                            @endif
                        </div>
                        @if ($link->network || $link->market)
                            <p class="t-small mt-1">{{ $link->network }}{{ $link->network && $link->market ? ' · ' : '' }}{{ $link->market }}</p>
                        @endif
                        <p class="t-body mt-1 min-w-0">
                            <span class="block truncate" title="{{ $link->url }}">{{ $link->url }}</span>
                            <a href="{{ $link->url }}" target="_blank" rel="noopener" class="font-medium text-primary hover:text-primary-hover">Abrir link</a>
                        </p>
                        @if ($link->notes)
                            <p class="t-small mt-1">{{ $link->notes }}</p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <details class="w-full">
                                <summary class="cursor-pointer text-sm font-medium text-primary hover:text-primary-hover">Editar link</summary>
                                <form method="POST" action="{{ route('affiliate-links.update', [$product, $link]) }}" class="mt-3 space-y-3" novalidate>
                                    @csrf
                                    @method('PUT')
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <x-ui.input label="Rótulo" name="label" type="text" required :value="old('label', $link->label)" />
                                        <x-ui.input label="URL" name="url" type="url" required :value="old('url', $link->url)" />
                                        <x-ui.input label="Rede" name="network" type="text" :value="old('network', $link->network)" />
                                        <x-ui.select label="Mercado" name="market" :options="config('locale-options.markets')" placeholder="Selecionar…" :value="old('market', $link->market)" />
                                    </div>
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
                                        <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', $link->is_primary)) class="h-4 w-4 rounded border-border accent-primary">
                                        Link principal
                                    </label>
                                    <x-ui.input label="Observações" name="notes" type="text" :value="old('notes', $link->notes)" />
                                    <x-ui.button variant="secondary" size="sm">Salvar link</x-ui.button>
                                </form>
                            </details>

                            @can('delete', $link)
                                <form method="POST" action="{{ route('affiliate-links.destroy', [$product, $link]) }}" onsubmit="return confirm('Excluir este link de afiliado?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-danger hover:text-danger-hover">Excluir</button>
                                </form>
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-6 border-t border-border pt-5">
            <h3 class="t-section-title">Novo link de afiliado</h3>
            <form method="POST" action="{{ route('affiliate-links.store', $product) }}" class="mt-3 space-y-3" novalidate>
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.input label="Rótulo" name="label" type="text" required helper="Ex.: Principal US, Instagram US." />
                    <x-ui.input label="URL" name="url" type="url" required />
                    <x-ui.input label="Rede" name="network" type="text" />
                    <x-ui.select label="Mercado" name="market" :options="config('locale-options.markets')" placeholder="Selecionar…" />
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary')) class="h-4 w-4 rounded border-border accent-primary">
                    Link principal
                </label>
                <x-ui.button variant="primary" size="sm">Adicionar link</x-ui.button>
            </form>
        </div>
    </x-ui.card>
</div>
@endsection
