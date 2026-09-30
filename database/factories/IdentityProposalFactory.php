<?php

namespace Database\Factories;

use App\Enums\IdentityProposalStatus;
use App\Models\IdentityProposal;
use App\Models\ReferenceAnalysis;
use App\Models\ReferenceProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentityProposal>
 */
class IdentityProposalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_profile_id' => ReferenceProfile::factory(),
            'reference_analysis_id' => ReferenceAnalysis::factory(),
            'status' => IdentityProposalStatus::Ready,
            'provider' => 'google',
            'model' => 'gemini-3.8-flash',
            'persona_data' => [
                'name' => fake()->words(3, true),
                'language' => 'en-US',
                'market' => 'US',
            ],
            'avatar_data' => [
                'name' => fake()->firstName(),
                'language' => 'en-US',
                'market' => 'US',
            ],
            'rationale' => [
                'persona' => [fake()->sentence()],
                'avatar' => [fake()->sentence()],
            ],
            'error_code' => null,
            'error_message' => null,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'applied_at' => null,
        ];
    }
}
