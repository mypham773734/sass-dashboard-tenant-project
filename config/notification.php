<?php

use App\Infrastructure\Notifications\Handlers\GenericNotificationHandler;
use App\Infrastructure\Notifications\Handlers\TenantMemberAddedHandler;
use App\Infrastructure\Notifications\Handlers\TenantMemberRemovedHandler;
use App\Infrastructure\Notifications\Handlers\TenantRoleChangedHandler;

return [
    'enabled' => env('NOTIFICATION_ENABLED', true),
    'queue'   => env('NOTIFICATION_QUEUE', 'notifications'),

    'event_types' => [
        // ========== SIMPLE: GenericHandler + config ==========
        'task.assigned' => [
            'enabled'           => env('NOTIFICATION_TASK_ASSIGNED', true),
            'handler'           => GenericNotificationHandler::class,
            'recipients'        => 'assignee_id',
            'title_template'    => '{actor_name} assigned you "{task_title}"',
            'url_template'      => 'task.show:{task_id}',
        ],

        'task.status_changed' => [
            'enabled'           => env('NOTIFICATION_TASK_STATUS_CHANGED', true),
            'handler'           => GenericNotificationHandler::class,
            'recipients'        => ['creator_id', 'assignee_id'],
            'title_template'    => 'Task status: {old_status} → {new_status}',
            'url_template'      => 'task.show:{task_id}',
        ],

        // ========== COMPLEX: BaseHandler subclasses ==========
        'tenant.member_added' => [
            'enabled'  => env('NOTIFICATION_TENANT_MEMBER_ADDED', true),
            'handler'  => TenantMemberAddedHandler::class,
        ],

        'tenant.member_removed' => [
            'enabled'  => env('NOTIFICATION_TENANT_MEMBER_REMOVED', true),
            'handler'  => TenantMemberRemovedHandler::class,
        ],

        'tenant.role_changed' => [
            'enabled'  => env('NOTIFICATION_TENANT_ROLE_CHANGED', true),
            'handler'  => TenantRoleChangedHandler::class,
        ],
    ],
];
