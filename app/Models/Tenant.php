<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project; 
use App\Models\Scopes\TenantScope; 

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'trial_ends_at',
        // 'settings',
    ];

    public static function booted(){
        static::addGlobalScope(new TenantScope); 
    }

    // Quan hệ N - N: 1 tenant có nhiều user
    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_user', 'tenant_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projects(){
        $this->hasMany(Project::class); 
    }
}
