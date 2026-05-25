<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleServiceLaborItemModel extends CoreModel
{


    protected $table = 'vehicle_service_labor_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [

        ]);
    }
}