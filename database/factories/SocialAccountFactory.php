<?php

namespace Database\Factories;

use App\Enums\SocialAccountStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'platform' => fake()->randomElement(SocialPlatform::cases()),
            'username' => fake()->unique()->userName(),
            'profile_url' => fake()->optional()->url(),
            'language' => fake()->randomElement(['en-US', 'pt-BR']),
            'market' => fake()->randomElement(['US', 'BR']),
            'niche' => fake()->optional()->words(2, true),
            'audience' => fake()->optional()->sentence(3),
            'tone' => fake()->optional()->sentence(3),
            'content_style' => fake()->optional()->sentence(4),
            'default_cta' => fake()->optional()->sentence(4),
            'posting_frequency' => fake()->optional()->randomElement(['2 Reels/day', '3 videos/day', '5 Shorts/week']),
            'status' => fake()->randomElement([SocialAccountStatus::Active, SocialAccountStatus::Paused]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
