<?php

namespace Database\Factories;

use App\Enums\VideoCompositionRequestStatus;
use App\Models\User;
use App\Models\VideoCompositionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoCompositionRequest>
 */
class VideoCompositionRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => VideoCompositionRequestStatus::Pending,
            'created_by' => User::factory(),
        ];
    }
}
