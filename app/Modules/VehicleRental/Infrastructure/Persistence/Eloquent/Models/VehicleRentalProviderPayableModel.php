<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalProviderPayableModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_rental_provider_payables';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'agreement_id' => 'integer',
            'provider_id' => 'integer',
            'provider_party_id' => 'integer',
            'rental_vehicle_id' => 'integer',
        ]);
    }
}
