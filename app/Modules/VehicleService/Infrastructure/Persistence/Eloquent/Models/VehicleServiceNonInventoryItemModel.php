<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleServiceNonInventoryItemModel extends CoreModel
{


    protected $table = 'vehicle_service_non_inventory_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'metadata' => 'array',
        ]);
    }
}
