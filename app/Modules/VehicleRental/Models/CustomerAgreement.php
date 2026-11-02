<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;

final class CustomerAgreement extends Agreement
{
    protected $table = 'vehicle_rental_customer_agreements';

    public function party(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->party();
    }

    public function vehicleUses(): HasMany
    {
        return $this->hasMany(VehicleUse::class, 'customer_agreement_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(CustomerAgreementHistory::class, 'agreement_id')->orderBy('row_version');
    }
}
