<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'tenant_id'];

    // Override Spatie's create() to include tenant_id in uniqueness check
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? \Spatie\Permission\Guard::getDefaultName(static::class);

        $query = static::query()
            ->where('name', $attributes['name'])
            ->where('guard_name', $attributes['guard_name']);

        if (isset($attributes['tenant_id'])) {
            $query->where('tenant_id', $attributes['tenant_id']);
        } else {
            $query->whereNull('tenant_id');
        }

        if ($query->exists()) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }
}
