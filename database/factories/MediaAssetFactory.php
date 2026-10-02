<?php

namespace Database\Factories;

use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => MediaAssetType::Image,
            'source' => MediaAssetSource::AiGenerated,
            'provider' => 'google',
            'model' => 'gemini-3.1-flash-image',
            'disk' => 'public',
            'path' => 'images/2026/09/'.fake()->uuid().'.jpg',
            'filename' => fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'width' => 768,
            'height' => 1365,
            'size_bytes' => fake()->numberBetween(50000, 500000),
            'aspect_ratio' => '9:16',
            'status' => MediaAssetStatus::Ready,
            'metadata' => ['image_size' => '1K'],
        ];
    }
}
