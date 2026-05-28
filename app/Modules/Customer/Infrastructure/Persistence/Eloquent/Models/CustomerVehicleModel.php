<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CustomerVehicleModel extends CoreModel
{
    protected $table = 'customer_vehicles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'customer_id' => 'integer',
            'vehicle_id' => 'integer',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }
}
