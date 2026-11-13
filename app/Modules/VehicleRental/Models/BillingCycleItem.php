<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\BillingCycleItemCategory;
use Modules\VehicleRental\Enums\BillingCycleItemType;

final class BillingCycleItem extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_billing_cycle_items';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'item_type' => BillingCycleItemType::class,
            'category' => BillingCycleItemCategory::class,
            'quantity' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'unit_rate' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
            'amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE,
        ]);
    }

    public function billingCycle(): BelongsTo
    {
        return $this->belongsTo(BillingCycle::class, 'billing_cycle_id');
    }
}
