<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class VehicleServiceJobPaymentLinkModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicle_service_job_payment_links';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), []);
    }
}
