<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleServiceJobCardLineModel extends CoreModel
{


    protected $table = 'vehicle_service_job_card_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'metadata' => 'array',
            'is_combo_component' => 'boolean',
            'requires_stock_movement' => 'boolean',
        ]);
    }
}
