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

        $this->app->bind(
            \App\Domain\Task\Repositories\TaskRepositoryInterface::class,
            \App\Infrastructure\Persistence\Repositories\EloquentTaskRepository::class,
        );


        $this->app->bind(
            \App\Services\Contracts\EnglishEgentServiceInterface::class,
            \App\Services\Impl\EnglishAgentService::class,
        );
    }
}
