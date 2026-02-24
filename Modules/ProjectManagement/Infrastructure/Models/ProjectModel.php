<?php

namespace Modules\ProjectManagement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ProjectModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'pm_projects';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'description',
        'customer_id',
        'status',
        'start_date',
        'end_date',
        'budget',
        'spent',
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

    public function tasks(): HasMany
    {
        return $this->hasMany(TaskModel::class, 'project_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(MilestoneModel::class, 'project_id');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntryModel::class, 'project_id');
    }
}
