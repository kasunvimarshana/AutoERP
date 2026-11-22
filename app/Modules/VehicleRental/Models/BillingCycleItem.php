<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\HR\Models\HrEmployee;
use Modules\Vehicle\Models\Vehicle;
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
            'vehicle_id' => 'integer',
            'driver_employee_id' => 'integer',
        ]);
    }

    public function billingCycle(): BelongsTo
    {
        return $this->belongsTo(BillingCycle::class, 'billing_cycle_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'driver_employee_id');
    }
}
