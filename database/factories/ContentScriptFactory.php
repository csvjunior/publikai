<?php

namespace Database\Factories;

use App\Enums\ContentScriptSource;
use App\Enums\ContentScriptStatus;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentScript>
 */
class ContentScriptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'content_blueprint_id' => ContentBlueprint::factory(),
            'persona_id' => Persona::factory(),
            'avatar_id' => Avatar::factory(),
            'title' => fake()->unique()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'status' => ContentScriptStatus::Ready,
            'language' => 'en-US',
            'market' => 'US',
            'objective' => fake()->optional()->sentence(3),
            'hook' => fake()->sentence(),
            'opening' => fake()->optional()->sentence(),
            'body' => fake()->paragraph(),
            'cta' => fake()->sentence(),
            'on_screen_text' => fake()->optional()->sentence(3),
            'visual_direction' => fake()->optional()->sentence(4),
            'voice_direction' => fake()->optional()->sentence(4),
            'duration_seconds' => fake()->numberBetween(10, 60),
            'generation_source' => ContentScriptSource::Manual,
            'status' => ContentScriptStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
