<?php

namespace Modules\Accounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class AccountingPeriodModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'accounting_periods';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'closed_by',
        'closed_at',
        'locked_by',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'closed_at'  => 'datetime',
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
