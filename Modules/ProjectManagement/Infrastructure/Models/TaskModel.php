<?php

namespace Modules\ProjectManagement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class TaskModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'pm_tasks';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'project_id',
        'title',
        'description',
        'assigned_to',
        'status',
        'priority',
        'due_date',
        'estimated_hours',
        'actual_hours',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
