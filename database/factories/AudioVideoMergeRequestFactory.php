<?php

namespace Database\Factories;

use App\Enums\AudioVideoDurationPolicy;
use App\Enums\AudioVideoMergeRequestStatus;
use App\Models\AudioVideoMergeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioVideoMergeRequest>
 */
class AudioVideoMergeRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => AudioVideoMergeRequestStatus::Pending,
            'duration_policy' => AudioVideoDurationPolicy::VideoMaster,
            'created_by' => User::factory(),
        ];
    }
}
