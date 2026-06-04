<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int         $id
 * @property int         $tenant_id
 * @property int         $project_id
 * @property int         $created_by
 * @property int|null    $assignee_id
 * @property string      $title
 * @property string|null $description
 * @property string      $status
 * @property string      $priority
 * @property int         $order
 * @property string|null $due_date
 * @property string|null $completed_at
 */
class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'created_by',
        'assignee_id',
        'title',
        'description',
        'status',
        'priority',
        'order',
        'due_date',
        'completed_at',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
