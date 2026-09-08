<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Models\Supplier;
use Modules\Vehicle\Models\Vehicle;

final class OwnerAgreement extends Agreement
{
    protected $table = 'vehicle_rental_owner_agreements';

    public function party(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function history(): HasMany
    {
        return $this->hasMany(OwnerAgreementHistory::class, 'agreement_id')->orderBy('row_version');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }
}
