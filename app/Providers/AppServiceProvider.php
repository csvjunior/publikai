<?php

namespace App\Providers;

use App\AI\Contracts\AiAudioProvider;
use App\AI\Contracts\AiImageProvider;
use App\AI\Contracts\AiTextProvider;
use App\AI\Contracts\AiVideoProvider;
use App\AI\Providers\GoogleGeminiAudioProvider;
use App\AI\Providers\GoogleGeminiImageProvider;
use App\AI\Providers\GoogleGeminiTextProvider;
use App\AI\Providers\GoogleOmniVideoProvider;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\AudioInspector;
use App\Services\AudioVideoMerger;
use App\Services\FfmpegAudioVideoMerger;
use App\Services\FfmpegVideoComposer;
use App\Services\FfprobeAudioInspector;
use App\Services\FfprobeVideoInspector;
use App\Services\VideoComposer;
use App\Services\VideoInspector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Provider de IA via container (troca futura sem tocar consumidores).
        $this->app->bind(AiTextProvider::class, function () {
            return match (config('ai.provider', 'google')) {
                default => new GoogleGeminiTextProvider,
            };
        });

        $this->app->bind(AiImageProvider::class, function () {
            return match (config('ai.provider', 'google')) {
                default => new GoogleGeminiImageProvider,
            };
        });

        $this->app->bind(AiVideoProvider::class, function () {
            return match (config('ai.provider', 'google')) {
                default => new GoogleOmniVideoProvider,
            };
        });

        $this->app->bind(AiAudioProvider::class, function () {
            return match (config('ai.provider', 'google')) {
                default => new GoogleGeminiAudioProvider,
            };
        });

        $this->app->bind(AudioInspector::class, FfprobeAudioInspector::class);

        $this->app->bind(VideoInspector::class, FfprobeVideoInspector::class);

        $this->app->bind(VideoComposer::class, FfmpegVideoComposer::class);

        $this->app->bind(AudioVideoMerger::class, FfmpegAudioVideoMerger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ponto de expansão para autorização futura.
        // Nesta Sprint: apenas distinção simples admin/operator.
        Gate::define('access-admin', fn (User $user) => $user->role === UserRole::Admin);

        // Apresentação de datas: converte para o timezone configurável sem
        // mutar a instância original. Ponto único reutilizável (calendário,
        // publicações, métricas, campanhas). Persistência segue em UTC.
        Carbon::macro('display', function (string $format = 'd/m/Y H:i'): string {
            /** @var Carbon $this */
            return $this->copy()->timezone(config('app.display_timezone', 'UTC'))->format($format);
        });
    }
}
