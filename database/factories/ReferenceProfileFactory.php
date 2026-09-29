<?php

namespace Database\Factories;

use App\Enums\ReferenceProfileStatus;
use App\Enums\SocialPlatform;
use App\Models\ReferenceProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferenceProfile>
 */
class ReferenceProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'platform' => fake()->randomElement(SocialPlatform::cases()),
            'username' => fake()->optional()->userName(),
            'profile_url' => fake()->url(),
            'language' => fake()->randomElement(['en-US', 'pt-BR']),
            'market' => fake()->randomElement(['US', 'BR']),
            'niche' => fake()->optional()->words(2, true),
            'reason' => fake()->optional()->sentence(),
            'status' => fake()->randomElement([ReferenceProfileStatus::Active, ReferenceProfileStatus::Paused]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
