<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionInTenant('tenant:edit', (int) $this->route('tenantId'));
    }

    public function rules(): array
    {
        return match ($this->route('section')) {
            'email' => [
                'email'                        => ['required', 'array'],
                'email.task_assigned'         => ['required', 'boolean'],
                'email.task_status_changed'   => ['required', 'boolean'],
                'email.tenant_member_added'   => ['required', 'boolean'],
                'email.tenant_member_removed' => ['required', 'boolean'],
                'email.tenant_role_changed'   => ['required', 'boolean'],
            ],
            'notifications' => [
                'notifications'                 => ['required', 'array'],
                'notifications.retention_days' => ['required', 'integer', 'in:7,14,30,60,90'],
            ],
            'localization' => [
                'localization'              => ['required', 'array'],
                'localization.timezone'    => ['required', 'timezone'],
                'localization.locale'      => ['required', 'string', 'in:en,vi'],
                'localization.date_format' => ['required', 'string', 'in:d/m/Y,Y-m-d,m/d/Y'],
            ],
            'members' => [
                'members'               => ['required', 'array'],
                'members.default_role' => ['required', 'string', 'in:member,manager,guest'],
            ],
            default => [],
        };
    }
}
