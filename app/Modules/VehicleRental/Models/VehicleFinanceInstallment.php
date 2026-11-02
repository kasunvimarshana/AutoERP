<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Models\Invoice;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class VehicleFinanceInstallment extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'vehicle_finance_installments';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','finance_agreement_id'=>'integer','installment_number'=>'integer','due_date'=>'date','principal_due'=>'decimal:6','interest_due'=>'decimal:6','fee_due'=>'decimal:6','tax_due'=>'decimal:6','total_due'=>'decimal:6','paid_amount'=>'decimal:6','balance_due'=>'decimal:6','status'=>VehicleFinanceInstallmentStatus::class,'invoice_id'=>'integer','metadata'=>'array']; }
    public function financeAgreement(): BelongsTo { return $this->belongsTo(VehicleFinanceAgreement::class, 'finance_agreement_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'invoice_id'); }
}
