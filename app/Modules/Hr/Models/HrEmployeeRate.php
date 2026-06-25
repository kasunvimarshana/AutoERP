<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\EmployeeRateType;
final class HrEmployeeRate extends TenantOwnedModel
{
    protected $table = 'hr_employee_rates';
    protected $guarded = ['id'];
    protected function casts(): array { return array_merge(parent::casts(), ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'employee_id' => 'integer', 'rate_type' => EmployeeRateType::class, 'amount' => 'decimal:6', 'currency_id' => 'integer', 'effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean']); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(CurrencyModel::class, 'currency_id'); }
}
