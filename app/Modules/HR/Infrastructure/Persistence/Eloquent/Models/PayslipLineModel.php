<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PayslipLineModel extends BaseModel
{
    use HasTenant;

    protected $table = 'hr_payslip_lines';

    protected $fillable = ['tenant_id', 'org_unit_id', 'row_version', 'payslip_id', 'payroll_item_id', 'item_name', 'item_code', 'type', 'amount', 'metadata'];

    protected $casts = ['org_unit_id' => 'integer', 'row_version' => 'integer', 'payslip_id' => 'integer', 'payroll_item_id' => 'integer', 'type' => 'string', 'amount' => 'decimal:6', 'metadata' => 'array'];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(PayslipModel::class, 'payslip_id');
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItemModel::class, 'payroll_item_id');
    }
}
