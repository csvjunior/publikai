<?php

namespace App\Services;

use App\AI\AiAudioGenerationInput;
use App\AI\Contracts\AiAudioProvider;
use App\AI\Exceptions\AiProviderException;
use App\Enums\AiGenerationStatus;
use App\Enums\AudioGenerationRequestStatus;
use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Models\AiGeneration;
use App\Models\AudioGenerationRequest;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Narração por voz/TTS (Sprint 5.6.2).
 * createRequest(): valida e enfileira (request web rápido).
 * process(): executado pelo Job — provider, WAV, FFprobe, MediaAsset
 * audio. Falhas nunca criam asset. Sem texto/base64 em banco ou logs.
 */
class AudioGenerationService
{
    public function __construct(
        protected AiAudioProvider $provider,
        protected AudioInspector $inspector,
    ) {}

    public function voices(): array
    {
        return (array) config('ai.google.audio.voices', []);
    }

    public function defaultVoice(): string
    {
        return (string) config('ai.google.audio.default_voice', 'Kore');
    }

    /**
     * @param  array{voice?: ?string, language?: ?string, style?: ?string, content_script_id?: ?int, content_production_id?: ?int}  $options
     *
     * @throws ValidationException
     */
    public function createRequest(string $text, array $options, ?int $createdBy = null): AudioGenerationRequest
    {
        $config = config('ai.google.audio');

        $options = [
            'voice' => $options['voice'] ?? $config['default_voice'],
            'language' => $options['language'] ?? null,
            'style' => $options['style'] ?? null,
            'content_script_id' => $options['content_script_id'] ?? null,
            'content_production_id' => $options['content_production_id'] ?? null,
        ];

        validator(
            ['text' => $text] + $options,
            [
                'text' => ['required', 'string', 'min:10', 'max:'.(int) $config['max_text_length']],
                'voice' => ['required', 'string', 'in:'.implode(',', array_keys($this->voices()))],
                'language' => ['nullable', 'string', 'max:10'],
                'content_script_id' => ['nullable', 'integer', 'exists:content_scripts,id'],
                'content_production_id' => ['nullable', 'integer', 'exists:content_productions,id'],
            ]
        )->validate();

        return AudioGenerationRequest::create([
            'status' => AudioGenerationRequestStatus::Pending,
            'text' => $text,
            'voice' => $options['voice'],
            'language' => $options['language'],
            'style' => $options['style'],
            'provider' => config('ai.provider', 'google'),
            'model' => (string) $config['model'],
            'content_script_id' => $options['content_script_id'],
            'content_production_id' => $options['content_production_id'],
            'created_by' => $createdBy,
        ]);
    }

    public function process(AudioGenerationRequest $request): void
    {
        $config = config('ai.google.audio');

        $log = AiGeneration::create([
            'provider' => $request->provider ?? config('ai.provider', 'google'),
            'model' => (string) ($request->model ?? $config['model']),
            'operation' => 'audio_generation',
            'status' => AiGenerationStatus::Pending,
        ]);

        $started = microtime(true);
        $path = null;

        try {
            $request->update([
                'status' => AudioGenerationRequestStatus::Processing,
                'started_at' => now(),
            ]);

            $result = $this->provider->generate(new AiAudioGenerationInput(
                text: $request->text,
                voice: (string) $request->voice,
                style: $request->style ?? null,
            ));

            $path = $this->store($result->audioData, $result->mimeType);

            $metadata = $this->inspector->inspect(Storage::disk('public')->path($path));

            if ($metadata === null) {
                throw new AiProviderException('invalid_audio', 'O provider não retornou um áudio válido.');
            }

            $asset = MediaAsset::create([
                'type' => MediaAssetType::Audio,
                'source' => MediaAssetSource::AiGenerated,
                'provider' => $result->provider,
                'model' => $result->model,
                'disk' => 'public',
                'path' => $path,
                'filename' => basename($path),
                'mime_type' => $metadata->mimeType,
                'duration_seconds' => $metadata->durationSeconds !== null ? (int) round($metadata->durationSeconds) : null,
                'size_bytes' => $metadata->sizeBytes ?? $result->sizeBytes(),
                'status' => MediaAssetStatus::Ready,
                'created_by' => $request->created_by,
                'metadata' => [
                    'voice' => $request->voice,
                    'language' => $request->language,
                    'sample_rate' => $metadata->sampleRate,
                    'channels' => $metadata->channels,
                ],
            ]);

            $request->update([
                'status' => AudioGenerationRequestStatus::Success,
                'media_asset_id' => $asset->id,
                'completed_at' => now(),
            ]);

            $log->update([
                'status' => AiGenerationStatus::Success,
                'duration_ms' => $result->durationMs,
                'external_request_id' => $result->externalRequestId,
                'metadata' => [
                    'voice' => $request->voice,
                    'language' => $request->language,
                    'mime_type' => $metadata->mimeType,
                    'sample_rate' => $metadata->sampleRate,
                ],
            ]);
        } catch (AiProviderException $e) {
            $this->cleanupPartial($path);

            $request->update([
                'status' => AudioGenerationRequestStatus::Failed,
                'error_code' => $e->errorCode,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $log->update([
                'status' => AiGenerationStatus::Failed,
                'error_code' => $e->errorCode,
                'metadata' => $e->details,
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            ]);
        }
    }

    protected function store(string $binary, string $mime): string
    {
        $ext = $mime === 'audio/l16' ? '.pcm' : '.wav';
        $path = 'audio/'.now()->format('Y/m').'/'.Str::uuid().$ext;

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    protected function cleanupPartial(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
