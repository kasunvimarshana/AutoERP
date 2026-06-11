<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use RuntimeException;

final class SalesNumberService
{
    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function next(
        int $tenantId,
        ?int $organizationUnitId,
        string $documentType,
        string $date,
        string $prefix,
    ): string {
        $result = $this->sequences->execute([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'document_type' => 'sales_'.$documentType,
            'period_type' => 'yearly',
            'at_date' => $date,
            'prefix' => $prefix.'-{PERIOD}-',
            'padding' => 6,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }
}
