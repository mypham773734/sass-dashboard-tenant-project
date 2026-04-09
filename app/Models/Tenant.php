<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project; 

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'trial_end_at',
        'settings',
    ];

    // Quan hệ N - N: 1 tenant có nhiều user
    public function users()
    {
        $this->belongsToMany(User::class, 'tenant_user', 'user_id', 'tenant_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projects(){
        $this->hasMany(Project::class); 
    }
}
