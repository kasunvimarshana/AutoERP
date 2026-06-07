<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;

final class VehicleServiceNumberService
{
    public function next(int $tenantId): string
    {
        $count = (int) DB::table('vehicle_service_jobs')->where('tenant_id', $tenantId)->count() + 1;

        return 'VSJ-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
