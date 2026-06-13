<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    private const string RoleSystemAdmin = 'systemAdmin'; 

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarAttribute(): ?string
    {
        return UserMeta::where('user_id', $this->id)->where('key', 'avatar')->value('value');
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user', 'user_id', 'tenant_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function rolesForTenant(int $tenantId)
    {
        $result =  $this->roles()
            ->where('roles.tenant_id', $tenantId)
            ->get();

        return $result; 
    }

    public function hasPermissionInTenant(string $permission, int $tenantId): bool
    {
        $result = $this->rolesForTenant($tenantId)
            ->flatMap(fn($role) => $role->permissions)
            ->contains('name', $permission);
        return $result;
    }

    public function hasRoleInTenant(string $role, int $tenantId): bool
    {
        return $this->rolesForTenant($tenantId)
            ->contains('name', $role);
    }

    public function getPrimaryRoleInTenant(int $tenantId): ?Role
    {
        return $this->rolesForTenant($tenantId)->first();
    }

    public function isAdminOfTenant(int $tenantId): bool
    {
        return $this->rolesForTenant($tenantId)
            ->whereIn('name', ['owner', 'admin'])
            ->isNotEmpty();
    }

    public function isSystemAdmin(){
        return $this->roles()->where('name', self::RoleSystemAdmin)->first(); 
    }
}
