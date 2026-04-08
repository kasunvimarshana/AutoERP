<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class PayrollModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'hr_payroll';

    protected $fillable = [
        'tenant_id', 'employee_id', 'pay_period_start', 'pay_period_end',
        'gross_salary', 'net_salary', 'deductions', 'allowances', 'bonuses',
        'currency', 'status', 'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id'    => 'integer',
        'employee_id'  => 'integer',
        'gross_salary' => 'float',
        'net_salary'   => 'float',
        'deductions'   => 'float',
        'allowances'   => 'float',
        'bonuses'      => 'float',
        'metadata'     => 'array',
    ];
}
