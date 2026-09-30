<?php

namespace App\Providers;

use App\AI\Contracts\AiTextProvider;
use App\AI\Providers\GoogleGeminiTextProvider;
use App\Enums\UserRole;
use App\Models\User;
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
