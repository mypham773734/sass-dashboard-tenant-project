<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TenantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);


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
