<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\RentalDepositStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalDepositRequirement extends TenantOwnedModel
{
    use ScopesRentalContext;
    protected $table = 'rental_deposit_requirements';
    protected $guarded = ['id'];
    protected function casts(): array { return ['row_version'=>'integer','tenant_id'=>'integer','organization_unit_id'=>'integer','agreement_id'=>'integer','required_amount'=>'decimal:6','currency_id'=>'integer','due_date'=>'date','is_refundable'=>'boolean','received_amount'=>'decimal:6','applied_amount'=>'decimal:6','refunded_amount'=>'decimal:6','forfeited_amount'=>'decimal:6','balance_amount'=>'decimal:6','status'=>RentalDepositStatus::class,'metadata'=>'array']; }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(CurrencyModel::class, 'currency_id'); }
    public function links(): HasMany { return $this->hasMany(RentalDepositLink::class, 'deposit_requirement_id')->orderBy('linked_at'); }
}
