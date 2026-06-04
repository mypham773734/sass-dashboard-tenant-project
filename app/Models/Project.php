<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int         $id
 * @property int         $tenant_id
 * @property int|null    $onwer_id
 * @property string      $name
 * @property string|null $description
 * @property string|null $status
 */
class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'onwer_id',
        'name',
        'description',
        'status',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
