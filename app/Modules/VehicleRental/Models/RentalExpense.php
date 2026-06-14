<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalExpense extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_expenses';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'usage_log_id' => 'integer',
            'expense_type' => RentalExpenseType::class,
            'amount' => 'decimal:6',
            'is_billable' => 'boolean',
            'attachments' => 'array',
            'status' => RentalExpenseStatus::class,
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function usageLog(): BelongsTo { return $this->belongsTo(RentalUsageLog::class, 'usage_log_id'); }
}
