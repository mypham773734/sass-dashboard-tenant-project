<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Project;
use App\Models\Scopes\TenantScope;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property bool        $is_active
 * @property string|null $trial_ends_at
 */
class Tenant extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'trial_ends_at',
    ];

    public static function booted(){
        static::addGlobalScope(new TenantScope); 
    }

    // Quan hệ N - N: 1 tenant có nhiều user
    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_user', 'tenant_id', 'user_id')
            ->withTimestamps();
    }

    public function projects(){
        return $this->hasMany(Project::class); 
    }
}
