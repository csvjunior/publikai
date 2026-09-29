<?php

namespace Database\Factories;

use App\Enums\AvatarStatus;
use App\Models\Avatar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Avatar>
 */
class AvatarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->firstName(),
            'apparent_age' => (string) fake()->numberBetween(20, 40),
            'gender_presentation' => fake()->optional()->randomElement(['Female', 'Male']),
            'ethnicity_description' => fake()->optional()->sentence(3),
            'hair' => fake()->optional()->words(3, true),
            'eyes' => fake()->optional()->word(),
            'skin' => fake()->optional()->words(3, true),
            'body_description' => fake()->optional()->sentence(4),
            'default_clothing' => fake()->optional()->words(3, true),
            'visual_style' => fake()->optional()->words(3, true),
            'preferred_scenarios' => fake()->optional()->sentence(4),
            'voice_description' => fake()->optional()->sentence(5),
            'language' => fake()->randomElement(['en-US', 'pt-BR']),
            'market' => fake()->randomElement(['US', 'BR']),
            'reference_notes' => fake()->optional()->sentence(),
            'status' => fake()->randomElement([AvatarStatus::Active, AvatarStatus::Paused]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
