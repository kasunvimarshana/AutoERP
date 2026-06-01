<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Seeders\Support;

final class VehicleServiceDocumentSeedCatalog
{
    public const DEFAULT_TENANT_CODE = 'DEFAULT';

    /** @return array<string, array{name: string, description: string}> */
    public static function documentTypes(): array
    {
        return [
            'VEHICLE_SERVICE_JOBCARD' => [
                'name' => 'Vehicle Service Job Card',
                'description' => 'Generic job card document generated from a vehicle service job.',
            ],
            'VEHICLE_SERVICE_INVOICE' => [
                'name' => 'Vehicle Service Invoice',
                'description' => 'Generic service invoice document generated from a vehicle service job.',
            ],
            'VEHICLE_SERVICE_REFUND' => [
                'name' => 'Vehicle Service Refund',
                'description' => 'Generic refund document generated from a vehicle service job payment context.',
            ],
        ];
    }
}
