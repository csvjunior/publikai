<?php

namespace App\Services;

use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Models\Avatar;
use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imagem de referência do Avatar (Sprint 5.5.2).
 * Upload manual → MediaAsset `uploaded` → vínculo ativo único no Avatar.
 * A imagem é só auxílio de consistência visual do personagem artificial:
 * sem identificação, biometria ou inferência de atributos sensíveis.
 * Asset anterior exclusivamente-referência é removido com segurança;
 * compartilhado com outro vínculo nunca é apagado automaticamente.
 */
class AvatarReferenceService
{
    /**
     * @var string[]
     */
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png'];

    /**
     * Anexa (ou substitui) a referência ativa do Avatar.
     */
    public function attach(Avatar $avatar, UploadedFile $file, ?int $userId = null): MediaAsset
    {
        $previousId = $avatar->reference_media_asset_id;

        $asset = DB::transaction(function () use ($avatar, $file, $userId) {
            $asset = $this->store($file, $userId);

            $avatar->update(['reference_media_asset_id' => $asset->id]);

            return $asset;
        });

        if ($previousId && $previousId !== $asset->id) {
            $this->deleteIfExclusive($previousId);
        }

        return $asset;
    }

    /**
     * Remove a referência ativa, preservando o Avatar.
     */
    public function detach(Avatar $avatar): void
    {
        $previousId = $avatar->reference_media_asset_id;

        if (! $previousId) {
            return;
        }

        $avatar->update(['reference_media_asset_id' => null]);

        $this->deleteIfExclusive($previousId);
    }

    /**
     * Persiste o upload como MediaAsset `uploaded` (UUID, path relativo).
     * Validação binária real: MIME + imagem decodificável + dimensões.
     */
    protected function store(UploadedFile $file, ?int $userId): MediaAsset
    {
        $binary = file_get_contents($file->getRealPath());
        $info = $binary === false ? false : @getimagesizefromstring($binary);

        abort_unless(
            $info !== false && in_array($info['mime'] ?? null, self::ALLOWED_MIMES, true),
            422,
            'O arquivo não é uma imagem JPEG/PNG válida.'
        );

        $mime = $info['mime'];
        $path = 'avatars/references/'.now()->format('Y/m').'/'.Str::uuid().($mime === 'image/png' ? '.png' : '.jpg');

        Storage::disk('public')->put($path, $binary);

        return MediaAsset::create([
            'type' => MediaAssetType::Image,
            'source' => MediaAssetSource::Uploaded,
            'provider' => null,
            'model' => null,
            'disk' => 'public',
            'path' => $path,
            'filename' => basename($path),
            'mime_type' => $mime,
            'width' => $info[0] ?: null,
            'height' => $info[1] ?: null,
            'size_bytes' => strlen($binary),
            'aspect_ratio' => null,
            'status' => MediaAssetStatus::Ready,
            'created_by' => $userId,
        ]);
    }

    /**
     * Apaga arquivo + linha só quando nenhum outro vínculo usa o asset:
     * outro Avatar, snapshot de request ou saída/vínculo de roteiro.
     */
    protected function deleteIfExclusive(int $mediaAssetId): void
    {
        $shared = Avatar::where('reference_media_asset_id', $mediaAssetId)->exists()
            || DB::table('image_generation_requests')
                ->where('reference_media_asset_id', $mediaAssetId)
                ->orWhere('media_asset_id', $mediaAssetId)
                ->exists()
            || DB::table('content_script_media_assets')
                ->where('media_asset_id', $mediaAssetId)
                ->exists();

        if ($shared) {
            return;
        }

        $asset = MediaAsset::find($mediaAssetId);

        if (! $asset) {
            return;
        }

        Storage::disk($asset->disk)->delete($asset->path);
        $asset->delete();
    }
}
