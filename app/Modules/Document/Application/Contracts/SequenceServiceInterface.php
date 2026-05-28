<?php

declare(strict_types=1);

namespace Modules\Document\Application\Contracts;

interface SequenceServiceInterface
{
    public function nextNumber(
        int $tenantId,
        ?int $organizationUnitId,
        string $documentType,
        ?string $date = null,
    ): string;
}
