<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TenantPolicy;
use App\Http\Listeners\AuthAuditListener;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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

        $this->app->bind(
            \App\Domain\Audit\Repositories\AuditRepositoryInterface::class,
            \App\Infrastructure\Persistence\Repositories\EloquentAuditRepository::class,
        );

        $this->app->bind(
            \App\Application\Audit\AuditLoggerInterface::class,
            \App\Infrastructure\Audit\QueuedAuditLogger::class,
        );

        Event::listen(Login::class,  [AuthAuditListener::class, 'handleLogin']);
        Event::listen(Failed::class, [AuthAuditListener::class, 'handleFailed']);
        Event::listen(Logout::class, [AuthAuditListener::class, 'handleLogout']);
    }
}
