{{-- Formulário de produto (create/edit). Blocos por seção; grids no desktop, uma coluna no mobile. --}}
@php
    $isEdit = isset($product) && $product->exists;
    // Operator não altera status de produto arquivado: exibe fixo, sem opção de saída.
    $statusLocked = $isEdit && $product->isArchived()
        && \Illuminate\Support\Facades\Gate::denies('updateStatus', [$product, \App\Enums\ProductStatus::Active->value]);

    $statusOptions = [];
    if (! $statusLocked) {
        foreach (\App\Enums\ProductStatus::cases() as $case) {
            if (\Illuminate\Support\Facades\Gate::allows('updateStatus', [$product ?? new \App\Models\Product, $case->value])) {
                $statusOptions[$case->value] = $case->label();
            }
        }
    }
@endphp

<div class="space-y-6">
    <x-ui.card title="Informações" description="Identificação do produto.">
        <div class="space-y-4">
            <x-ui.input label="Nome" name="name" type="text" required autofocus :value="old('name', $product->name ?? null)" />
            <x-ui.textarea label="Descrição" name="description" :rows="3" :value="old('description', $product->description ?? null)" />
            <x-ui.input label="Categoria" name="category" type="text" :value="old('category', $product->category ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Mercado" description="Onde e em que idioma o produto será promovido.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.select label="Idioma" name="language" :options="config('locale-options.languages')" placeholder="Selecionar…" :value="old('language', $product->language ?? null)" />
            <x-ui.select label="Mercado" name="market" :options="config('locale-options.markets')" placeholder="Selecionar…" :value="old('market', $product->market ?? null)" />
            <x-ui.select label="Moeda" name="currency" :options="config('products.currencies')" placeholder="Selecionar…" :value="old('currency', $product->currency ?? null)" />
            <x-ui.input label="Preço" name="price" type="number" step="0.01" min="0" :value="old('price', $product->price ?? null)" />
        </div>
    </x-ui.card>

    <x-ui.card title="Afiliado" description="Rede e comissão do programa de afiliados.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Rede" name="affiliate_network" type="text" :value="old('affiliate_network', $product->affiliate_network ?? null)" />
            <x-ui.select label="Tipo de comissão" name="commission_type" :options="config('products.commission_types')" placeholder="Selecionar…" :value="old('commission_type', $product->commission_type ?? null)" />
            <div class="sm:col-span-2">
                <x-ui.input label="Valor da comissão" name="commission_value" type="number" step="0.01" min="0" helper="Percentual ou valor fixo, conforme o tipo." :value="old('commission_value', $product->commission_value ?? null)" />
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Link original" description="Página oficial do produto (não é o link de afiliado).">
        <x-ui.input label="URL do produto" name="product_url" type="url" :value="old('product_url', $product->product_url ?? null)" />
    </x-ui.card>

    <x-ui.card title="Controle" description="Status e observações internas.">
        <div class="space-y-4">
            @if ($statusLocked)
                <div>
                    <p class="t-label">Status</p>
                    <p class="mt-1"><x-ui.badge variant="neutral">Arquivado</x-ui.badge></p>
                    <input type="hidden" name="status" value="archived">
                    <p class="t-small mt-1">Somente um administrador pode reativar este produto.</p>
                </div>
            @else
                <x-ui.select label="Status" name="status" :options="$statusOptions" required :value="old('status', isset($product) ? $product->status->value : 'active')" />
            @endif
            <x-ui.textarea label="Observações" name="notes" :rows="3" :value="old('notes', $product->notes ?? null)" />
        </div>
    </x-ui.card>

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        <x-ui.button :href="$isEdit ? route('products.show', $product) : route('products.index')" variant="outline">Cancelar</x-ui.button>
        <x-ui.button variant="primary" type="submit">{{ $isEdit ? 'Salvar alterações' : 'Cadastrar produto' }}</x-ui.button>
    </div>
</div>
