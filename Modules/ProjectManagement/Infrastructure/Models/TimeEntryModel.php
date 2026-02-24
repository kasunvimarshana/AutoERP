<?php

namespace Modules\ProjectManagement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class TimeEntryModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'pm_time_entries';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'project_id',
        'task_id',
        'user_id',
        'hours',
        'description',
        'entry_date',
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
