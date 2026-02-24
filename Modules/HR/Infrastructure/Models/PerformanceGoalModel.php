<?php

namespace Modules\HR\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PerformanceGoalModel extends Model
{
    use HasTenantScope;

    protected $table = 'hr_performance_goals';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'employee_id',
        'title',
        'description',
        'period',
        'year',
        'due_date',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'due_date'     => 'date',
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
