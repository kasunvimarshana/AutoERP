<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleRentalRunningChartModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_rental_running_charts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'agreement_id' => 'integer',
            'lessee_agreement_id' => 'integer',
            'lessor_agreement_id' => 'integer',
            'paired_running_chart_id' => 'integer',
            'rental_vehicle_id' => 'integer',
        ]);
    }
}
