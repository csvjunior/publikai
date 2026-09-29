<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ponto de expansão para autorização futura.
        // Nesta Sprint: apenas distinção simples admin/operator.
        Gate::define('access-admin', fn (User $user) => $user->role === UserRole::Admin);
    }
}
