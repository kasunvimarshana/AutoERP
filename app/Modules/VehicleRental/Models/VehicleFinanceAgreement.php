<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\VehicleFinanceAgreementStatus;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentFrequency;
use Modules\VehicleRental\Enums\VehicleFinanceInterestMethod;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class VehicleFinanceAgreement extends TenantOwnedModel
{
    use ScopesRentalContext;
    use SoftDeletes;

    protected $table = 'vehicle_finance_agreements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'vehicle_id' => 'integer',
            'agreement_date' => 'date',
            'starts_at' => 'datetime',
            'matures_at' => 'datetime',
            'currency_id' => 'integer',
            'principal_amount' => 'decimal:6',
            'initial_deposit_amount' => 'decimal:6',
            'residual_value' => 'decimal:6',
            'interest_method' => VehicleFinanceInterestMethod::class,
            'annual_interest_rate' => 'decimal:6',
            'installment_frequency' => VehicleFinanceInstallmentFrequency::class,
            'installment_count' => 'integer',
            'payment_term_days' => 'integer',
            'tax_group_id' => 'integer',
            'status' => VehicleFinanceAgreementStatus::class,
            'metadata' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class, 'vehicle_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(CurrencyModel::class, 'currency_id'); }
    public function taxGroup(): BelongsTo { return $this->belongsTo(TaxGroup::class, 'tax_group_id'); }
    public function installments(): HasMany { return $this->hasMany(VehicleFinanceInstallment::class, 'finance_agreement_id')->orderBy('installment_number'); }
}
