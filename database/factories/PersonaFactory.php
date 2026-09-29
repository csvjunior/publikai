<?php

namespace Database\Factories;

use App\Enums\PersonaStatus;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'language' => fake()->randomElement(['en-US', 'pt-BR']),
            'market' => fake()->randomElement(['US', 'BR']),
            'audience' => fake()->optional()->sentence(4),
            'personality' => fake()->optional()->sentence(4),
            'tone' => fake()->optional()->sentence(3),
            'communication_style' => fake()->optional()->sentence(5),
            'vocabulary' => fake()->optional()->sentence(3),
            'expressions' => fake()->optional()->paragraph(),
            'content_preferences' => fake()->optional()->sentence(4),
            'avoidances' => fake()->optional()->sentence(4),
            'default_cta_style' => fake()->optional()->sentence(4),
            'status' => fake()->randomElement([PersonaStatus::Active, PersonaStatus::Paused]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
