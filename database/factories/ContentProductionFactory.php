<?php

namespace Database\Factories;

use App\Enums\ContentProductionStatus;
use App\Enums\ContentProductionStep;
use App\Models\ContentProduction;
use App\Models\ContentScript;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentProduction>
 */
class ContentProductionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => ContentProductionStatus::Pending,
            'current_step' => ContentProductionStep::Preparing,
            'force_new' => false,
            'content_script_id' => ContentScript::factory(),
            'created_by' => User::factory(),
        ];
    }
}
