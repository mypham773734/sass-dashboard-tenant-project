<?php

return [

    'enabled' => env('MAIL_SERVICE_ENABLED', true),
    'queue'   => env('MAIL_SERVICE_QUEUE', 'mail'),

    'email_types' => [

        'user_invitation' => [
            'enabled'  => env('USER_INVITATION_ENABLED', true),
            'handler'  => \App\Infrastructure\Mail\Handlers\UserInvitationHandler::class,
            'mailable' => \App\Infrastructure\Mail\Mailables\UserInvitationMailable::class,
            'template' => 'emails.user-invitation',
        ],

        'tenant_notification' => [
            'enabled'  => env('TENANT_NOTIFICATION_ENABLED', true),
            'handler'  => \App\Infrastructure\Mail\Handlers\TenantNotificationHandler::class,
            'mailable' => \App\Infrastructure\Mail\Mailables\TenantNotificationMailable::class,
            'template' => 'emails.tenant-notification',
        ],

        'audit_digest' => [
            'enabled'  => env('AUDIT_DIGEST_ENABLED', true),
            'schedule' => env('AUDIT_DIGEST_SCHEDULE', 'daily_08_00'),
            'handler'  => \App\Infrastructure\Mail\Handlers\AuditDigestHandler::class,
            'mailable' => \App\Infrastructure\Mail\Mailables\AuditDigestMailable::class,
            'template' => 'emails.audit-digest',
        ],

    ],

];
