<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class VehicleFinanceStatusHistory extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'vehicle_finance_status_histories';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','finance_agreement_id'=>'integer','installment_id'=>'integer','changed_at'=>'datetime','metadata'=>'array']; }
    public function financeAgreement(): BelongsTo { return $this->belongsTo(VehicleFinanceAgreement::class, 'finance_agreement_id'); }
    public function installment(): BelongsTo { return $this->belongsTo(VehicleFinanceInstallment::class, 'installment_id'); }
}
