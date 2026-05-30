<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleServiceJobCardModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_service_job_cards';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'vehicle_ownership_id' => 'integer',
            'vehicle_owner_id' => 'integer',
            'service_customer_id' => 'integer',
            'linked_customer_id' => 'integer',
            'billing_customer_id' => 'integer',
            'payer_id' => 'integer',
            'party_context' => 'array',
        ]);
    }
}
