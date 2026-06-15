<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Enums\RentalExpenseFinancialTreatment;
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
            'expense_date' => 'date',
            'currency_id' => 'integer',
            'amount' => 'decimal:6',
            'tax_group_id' => 'integer',
            'tax_amount' => 'decimal:6',
            'withholding_amount' => 'decimal:6',
            'original_net_amount' => 'decimal:6',
            'original_tax_group_id' => 'integer',
            'original_tax_amount' => 'decimal:6',
            'original_gross_amount' => 'decimal:6',
            'original_withholding_amount' => 'decimal:6',
            'recoverable_input_tax_amount' => 'decimal:6',
            'recovery_base_amount' => 'decimal:6',
            'recovery_tax_group_id' => 'integer',
            'recovery_tax_amount' => 'decimal:6',
            'recovery_withholding_amount' => 'decimal:6',
            'markup_amount' => 'decimal:6',
            'generated_charge_id' => 'integer',
            'financial_treatment' => RentalExpenseFinancialTreatment::class,
            'is_billable' => 'boolean',
            'is_recoverable' => 'boolean',
            'is_reimbursable' => 'boolean',
            'responsible_party_id' => 'integer',
            'attachments' => 'array',
            'status' => RentalExpenseStatus::class,
            'submitted_by' => 'integer',
            'submitted_at' => 'datetime',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'rejected_by' => 'integer',
            'rejected_at' => 'datetime',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(RentalUsageLog::class, 'usage_log_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RentalStatusHistory::class, 'expense_id')->latest('changed_at');
    }
}
