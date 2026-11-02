<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use RuntimeException;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;

final class RentalNumberService
{
    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function next(int $tenantId, ?int $organizationUnitId, string $documentType, string $prefix): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'document_type' => $documentType,
            'prefix' => $prefix,
            'padding' => 6,
            'period_type' => 'infinite',
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        $value = $result->valueOrFail();

        return (string) ($value['generated_number'] ?? '');
    }
}
