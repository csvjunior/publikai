<?php

namespace Database\Factories;

use App\Enums\ReferenceContentStatus;
use App\Models\ReferenceContent;
use App\Models\ReferenceProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferenceContent>
 */
class ReferenceContentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_profile_id' => ReferenceProfile::factory(),
            'url' => fake()->url(),
            'title' => fake()->optional()->sentence(4),
            'content_type' => fake()->randomElement(['ugc', 'demonstration', 'problem_solution']),
            'observed_hook' => fake()->optional()->sentence(3),
            'observed_structure' => fake()->optional()->sentence(6),
            'observed_cta' => fake()->optional()->sentence(3),
            'observed_style' => fake()->optional()->sentence(3),
            'duration_seconds' => fake()->optional()->numberBetween(10, 300),
            'performance_notes' => fake()->optional()->sentence(),
            'why_it_works' => fake()->optional()->sentence(),
            'status' => fake()->randomElement([ReferenceContentStatus::Active, ReferenceContentStatus::Paused]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
