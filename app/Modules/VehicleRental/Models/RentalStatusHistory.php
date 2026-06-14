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
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo { return $this->belongsTo(RentalReservation::class, 'reservation_id'); }
    public function agreement(): BelongsTo { return $this->belongsTo(RentalAgreement::class, 'agreement_id'); }
}
