<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PayrollModel extends BaseModel
{
    protected $table = 'hr_payroll_records';
    protected $fillable = [
        'tenant_id', 'employee_id', 'period_year', 'period_month',
        'basic_salary', 'allowances', 'deductions', 'tax_amount', 'net_salary',
        'status', 'payment_date', 'payment_reference', 'breakdown',
        'processed_by_id', 'processed_at',
    ];
    protected $casts = [
        'id'              => 'int',
        'tenant_id'       => 'int',
        'employee_id'     => 'int',
        'period_year'     => 'int',
        'period_month'    => 'int',
        'processed_by_id' => 'int',
        'basic_salary'    => 'float',
        'allowances'      => 'float',
        'deductions'      => 'float',
        'tax_amount'      => 'float',
        'net_salary'      => 'float',
        'breakdown'       => 'array',
        'payment_date'    => 'datetime',
        'processed_at'    => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];
}
