<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\RentalCalculationSourceKind;
use Modules\VehicleRental\Enums\RentalCalculationSourceStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalCalculationSource extends TenantOwnedModel
{
    use ScopesRentalContext;

    protected $table = 'rental_calculation_sources';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'calculation_run_id' => 'integer',
            'source_kind' => RentalCalculationSourceKind::class,
            'usage_context_id' => 'integer',
            'expense_allocation_id' => 'integer',
            'status' => RentalCalculationSourceStatus::class,
            'metadata' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(RentalCalculationRun::class, 'calculation_run_id');
    }

    public function usageContext(): BelongsTo
    {
        return $this->belongsTo(RentalUsageContext::class, 'usage_context_id');
    }

    public function expenseAllocation(): BelongsTo
    {
        return $this->belongsTo(RentalExpenseAllocation::class, 'expense_allocation_id');
    }
}
