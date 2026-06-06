<?php

namespace App\Http\Listeners;

use App\Infrastructure\Queue\Jobs\WriteAuditLogJob;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuthAuditListener
{
    public function handleLogin(Login $event): void
    {
        WriteAuditLogJob::dispatch([
            'tenant_id'   => null,
            'user_id'     => $event->user->id,
            'action'      => 'auth.login',
            'entity_type' => 'User',
            'entity_id'   => $event->user->id,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        WriteAuditLogJob::dispatch([
            'tenant_id'   => null,
            'user_id'     => null,
            'action'      => 'auth.login_failed',
            'entity_type' => null,
            'entity_id'   => null,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'metadata'    => ['email' => $event->credentials['email'] ?? null],
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        WriteAuditLogJob::dispatch([
            'tenant_id'   => null,
            'user_id'     => $event->user?->id,
            'action'      => 'auth.logout',
            'entity_type' => 'User',
            'entity_id'   => $event->user?->id,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
