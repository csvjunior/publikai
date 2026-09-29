<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação compartilhada de produto (create/update).
 * Listas controladas vêm de config/products.php (banco guarda os códigos).
 */
abstract class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'product_url' => ['nullable', 'url', 'max:2048'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['nullable', Rule::in(array_keys(config('products.currencies')))],
            'market' => ['nullable', Rule::in(array_keys(config('products.markets')))],
            'language' => ['nullable', Rule::in(array_keys(config('products.languages')))],
            'affiliate_network' => ['nullable', 'string', 'max:255'],
            'commission_type' => ['nullable', Rule::in(array_keys(config('products.commission_types')))],
            'commission_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
