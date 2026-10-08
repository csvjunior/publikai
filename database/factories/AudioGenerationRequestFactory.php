<?php

namespace Database\Factories;

use App\Enums\AudioGenerationRequestStatus;
use App\Models\AudioGenerationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioGenerationRequest>
 */
class AudioGenerationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => AudioGenerationRequestStatus::Pending,
            'text' => fake()->sentence(),
            'voice' => 'Kore',
            'language' => 'en-US',
            'provider' => 'google',
            'model' => 'gemini-3.8-flash-tts',
            'created_by' => User::factory(),
        ];
    }
}
