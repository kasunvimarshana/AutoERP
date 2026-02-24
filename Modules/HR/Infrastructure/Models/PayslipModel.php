<?php

namespace Modules\HR\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PayslipModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'hr_payslips';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'payroll_run_id',
        'employee_id',
        'gross_salary',
        'deductions',
        'net_salary',
        'status',
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
