<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::withCount('affiliateLinks')->latest()->paginate(15);

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create');
    }

    public function store(StoreProductRequest $request, ProductService $service): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();

        $this->authorize('updateStatus', [new Product, $data['status']]);

        $product = $service->create($data);

        return redirect()->route('products.show', $product)->with('status', 'Produto cadastrado.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load('affiliateLinks');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $service): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        if (($data['status'] ?? null) !== $product->status->value) {
            $this->authorize('updateStatus', [$product, $data['status']]);
        }

        $service->update($product, $data);

        return redirect()->route('products.show', $product)->with('status', 'Produto atualizado.');
    }
}
