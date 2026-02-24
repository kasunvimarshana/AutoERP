<?php

namespace Modules\Leave\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class LeaveAllocationModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'leave_allocations';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'total_days',
        'used_days',
        'period_label',
        'valid_from',
        'valid_to',
        'notes',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'valid_from'  => 'date',
        'valid_to'    => 'date',
        'approved_at' => 'datetime',
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
