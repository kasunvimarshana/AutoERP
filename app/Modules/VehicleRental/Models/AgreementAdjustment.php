<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Models\Invoice;
use Modules\User\Models\UserModel;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementAdjustmentCategory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;

final class AgreementAdjustment extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_agreement_adjustments';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'adjustment_type' => AgreementAdjustmentType::class,
            'category' => AgreementAdjustmentCategory::class,
            'status' => AgreementAdjustmentStatus::class,
            'amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'original_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'tax_amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'authorized_at' => 'immutable_datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenant, int $organization): Builder
    {
        return $query->forTenant($tenant)->where('organization_unit_id', $organization);
    }

    public function customerAgreement(): BelongsTo
    {
        return $this->belongsTo(CustomerAgreement::class, 'customer_agreement_id');
    }

    public function ownerAgreement(): BelongsTo
    {
        return $this->belongsTo(OwnerAgreement::class, 'owner_agreement_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'actor_id');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'authorized_by_user_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(AgreementAdjustmentHistory::class, 'adjustment_id');
    }
}
