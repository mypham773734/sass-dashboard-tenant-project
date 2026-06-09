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
use App\Domain\Tenant\Repositories\TenantRepositoryInterface; 
use App\Infrastructure\Persistence\Repositories\EloquentTenantRepository; 
use App\Domain\Project\Repositories\ProjectRepositoryInterface; 
use App\Infrastructure\Persistence\Repositories\EloquentProjectRepository;
use App\Domain\Task\Repositories\TaskRepositoryInterface; 
use App\Infrastructure\Persistence\Repositories\EloquentTaskRepository;  
use App\Services\Contracts\EnglishEgentServiceInterface; 
use App\Services\Impl\EnglishAgentService; 
use App\Domain\Audit\Repositories\AuditRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentAuditRepository;
use App\Application\Audit\AuditLoggerInterface;
use App\Infrastructure\Audit\QueuedAuditLogger;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\Application\Mail\Contracts\MailServiceInterface;
use App\Infrastructure\Mail\MailService;
use App\Shared\Tenant\TenantContext;
use App\Shared\Auth\AuthContext;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->singleton(TenantContext::class); 
        $this->app->singleton(AuthContext::class); 

        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);


        // ── Clean Architecture bindings (new) ─────────────────────────────────
        $this->app->bind(
            TenantRepositoryInterface::class,
            EloquentTenantRepository::class,
        );

        $this->app->bind(
            ProjectRepositoryInterface::class,
            EloquentProjectRepository::class,
        );

        $this->app->bind(
            TaskRepositoryInterface::class,
            EloquentTaskRepository::class,
        );


        $this->app->bind(
            EnglishEgentServiceInterface::class,
            EnglishAgentService::class,
        );

        $this->app->bind(
            AuditRepositoryInterface::class,
            EloquentAuditRepository::class,
        );

        $this->app->bind(
            AuditLoggerInterface::class,
            QueuedAuditLogger::class,
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class,
        );

        $this->app->bind(
            MailServiceInterface::class,
            MailService::class,
        );

        Event::listen(Login::class,  [AuthAuditListener::class, 'handleLogin']);
        Event::listen(Failed::class, [AuthAuditListener::class, 'handleFailed']);
        Event::listen(Logout::class, [AuthAuditListener::class, 'handleLogout']);
    }
}
