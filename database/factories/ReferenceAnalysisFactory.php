<?php

namespace Database\Factories;

use App\Enums\ReferenceAnalysisStatus;
use App\Models\ReferenceAnalysis;
use App\Models\ReferenceProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferenceAnalysis>
 */
class ReferenceAnalysisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_profile_id' => ReferenceProfile::factory(),
            'status' => ReferenceAnalysisStatus::Success,
            'provider' => 'google',
            'model' => 'gemini-3.8-flash',
            'summary' => fake()->sentence(),
            'dominant_hooks' => [fake()->sentence(3)],
            'content_structures' => [fake()->sentence(4)],
            'cta_patterns' => [fake()->sentence(3)],
            'visual_patterns' => [fake()->sentence(3)],
            'communication_patterns' => [fake()->sentence(3)],
            'audience_signals' => [fake()->sentence(3)],
            'content_angles' => [fake()->sentence(3)],
            'repeated_patterns' => [fake()->sentence(3)],
            'risks' => [fake()->sentence(3)],
            'recommendations' => [fake()->sentence(3)],
            'confidence' => fake()->randomFloat(2, 0.4, 0.95),
            'error_code' => null,
            'error_message' => null,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ];
    }
}
