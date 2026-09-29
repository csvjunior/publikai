<?php

namespace Database\Factories;

use App\Models\AffiliateLink;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliateLink>
 */
class AffiliateLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'label' => fake()->words(2, true),
            'url' => fake()->url(),
            'network' => fake()->optional()->company(),
            'market' => fake()->randomElement(['US', 'BR']),
            'is_primary' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
