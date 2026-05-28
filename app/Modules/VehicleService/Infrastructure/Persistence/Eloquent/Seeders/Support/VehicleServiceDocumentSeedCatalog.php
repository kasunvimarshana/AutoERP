<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Seeders\Support;

final class VehicleServiceDocumentSeedCatalog
{
    public const DEFAULT_TENANT_CODE = 'DEFAULT';

    /** @return array<string, array{name: string, category: string}> */
    public static function documentTypes(): array
    {
        return [
            'VEHICLE_SERVICE_JOBCARD' => [
                'name' => 'Vehicle Service Job Card',
                'category' => 'service',
            ],
            'VEHICLE_SERVICE_INVOICE' => [
                'name' => 'Vehicle Service Invoice',
                'category' => 'sales',
            ],
            'VEHICLE_SERVICE_REFUND' => [
                'name' => 'Vehicle Service Refund',
                'category' => 'sales',
            ],
        ];
    }
}
