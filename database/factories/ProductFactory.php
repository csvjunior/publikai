<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->optional()->word(),
            'product_url' => fake()->optional()->url(),
            'price' => fake()->optional()->randomFloat(2, 10, 500),
            'currency' => fake()->randomElement(['USD', 'BRL']),
            'market' => fake()->randomElement(['US', 'BR']),
            'language' => fake()->randomElement(['en-US', 'pt-BR']),
            'affiliate_network' => fake()->optional()->company(),
            'commission_type' => fake()->randomElement(['percent', 'fixed']),
            'commission_value' => fake()->optional()->randomFloat(2, 1, 50),
            'status' => fake()->randomElement([ProductStatus::Active, ProductStatus::Paused]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
