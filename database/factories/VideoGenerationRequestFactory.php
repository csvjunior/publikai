<?php

namespace Database\Factories;

use App\Enums\VideoGenerationRequestStatus;
use App\Models\User;
use App\Models\VideoGenerationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoGenerationRequest>
 */
class VideoGenerationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => VideoGenerationRequestStatus::Pending,
            'prompt' => fake()->sentence(),
            'aspect_ratio' => '9:16',
            'duration_seconds' => 8,
            'provider' => 'google',
            'model' => 'gemini-omni-1.1-flash',
            'created_by' => User::factory(),
        ];
    }
}
