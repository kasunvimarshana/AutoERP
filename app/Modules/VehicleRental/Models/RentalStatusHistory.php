<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalStatusHistory extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_status_histories';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'reservation_id' => 'integer',
            'agreement_id' => 'integer',
            'usage_log_id' => 'integer',
            'expense_id' => 'integer',
            'charge_id' => 'integer',
            'agreement_vehicle_link_id' => 'integer',
            'subject_id' => 'integer',
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RentalReservation::class, 'reservation_id');
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(RentalUsageLog::class, 'usage_log_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(RentalExpense::class, 'expense_id');
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(RentalCharge::class, 'charge_id');
    }

    public function agreementVehicleLink(): BelongsTo
    {
        return $this->belongsTo(RentalAgreementVehicleLink::class, 'agreement_vehicle_link_id');
    }
}
