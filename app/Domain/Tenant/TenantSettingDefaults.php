<?php

namespace App\Domain\Tenant;

class TenantSettingDefaults
{
    public const DEFAULTS = [
        'email' => [
            'task_assigned' => true,
            'task_status_changed' => true,
            'tenant_member_added' => true,
            'tenant_member_removed' => true,
            'tenant_role_changed' => true,
        ],
        'notifications' => [
            'retention_days' => 30,
        ],
        'localization' => [
            'timezone' => 'UTC',
            'locale' => 'en',
            'date_format' => 'd/m/Y',
        ],
        'members' => [
            'default_role' => 'member',
        ],
    ];
}
