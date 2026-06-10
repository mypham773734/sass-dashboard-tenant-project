<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Override;
use App\Models\Scopes\TenantScope;

class TenantSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    #[Override]
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }
}
