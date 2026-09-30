<?php

namespace Database\Factories;

use App\Enums\ContentBlueprintStatus;
use App\Models\ContentBlueprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentBlueprint>
 */
class ContentBlueprintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->optional()->sentence(),
            'content_type' => fake()->randomElement(['ugc', 'demonstration', 'curiosity']),
            'objective' => fake()->optional()->sentence(3),
            'hook_pattern' => fake()->optional()->sentence(4),
            'structure_pattern' => fake()->optional()->sentence(5),
            'cta_pattern' => fake()->optional()->sentence(3),
            'visual_style' => fake()->optional()->sentence(4),
            'communication_style' => fake()->optional()->sentence(4),
            'recommended_duration_seconds' => fake()->optional()->numberBetween(10, 120),
            'language' => fake()->randomElement(['en-US', 'pt-BR']),
            'market' => fake()->randomElement(['US', 'BR']),
            'niche' => fake()->optional()->words(2, true),
            'status' => fake()->randomElement([ContentBlueprintStatus::Active, ContentBlueprintStatus::Paused]),
            'source_type' => 'manual',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
