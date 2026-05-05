<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PayrollRunModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_payroll_runs';

    protected $fillable = ['tenant_id', 'period_start', 'period_end', 'status', 'processed_at', 'approved_at', 'approved_by', 'total_gross', 'total_deductions', 'total_net', 'metadata'];

    protected $casts = ['tenant_id' => 'integer', 'approved_by' => 'integer', 'period_start' => 'date', 'period_end' => 'date', 'processed_at' => 'datetime', 'approved_at' => 'datetime', 'metadata' => 'array'];

    public function payslips(): HasMany
    {
        return $this->hasMany(PayslipModel::class, 'payroll_run_id');
    }
}
