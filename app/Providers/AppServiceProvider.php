<?php

namespace App\Providers;

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
        // ── Clean Architecture bindings (new) ─────────────────────────────────
        $this->app->bind(
            \App\Domain\Tenant\Repositories\TenantRepositoryInterface::class,
            \App\Infrastructure\Persistence\Repositories\EloquentTenantRepository::class,
        );

        $this->app->bind(
            \App\Domain\Project\Repositories\ProjectRepositoryInterface::class,
            \App\Infrastructure\Persistence\Repositories\EloquentProjectRepository::class,
        );

        // ── Legacy Service bindings (kept while parallel refactor is in progress) ──
        $this->app->bind(
            \App\Services\Contracts\TenantServiceInterface::class,
            \App\Services\Impl\TenantService::class,
        );

        $this->app->bind(
            \App\Services\Contracts\ProjectServiceInterface::class,
            \App\Services\Impl\ProjectService::class,
        );

        $this->app->bind(
            \App\Services\Contracts\EnglishEgentServiceInterface::class,
            \App\Services\Impl\EnglishAgentService::class,
        );
    }
}
