<?php

namespace Database\Factories;

use App\Enums\ImageGenerationRequestStatus;
use App\Models\ImageGenerationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImageGenerationRequest>
 */
class ImageGenerationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => ImageGenerationRequestStatus::Pending,
            'prompt' => fake()->sentence(),
            'aspect_ratio' => '9:16',
            'image_size' => '1K',
            'mime_type' => 'image/jpeg',
            'provider' => 'google',
            'model' => 'gemini-3.1-flash-image',
            'created_by' => User::factory(),
        ];
    }
}
