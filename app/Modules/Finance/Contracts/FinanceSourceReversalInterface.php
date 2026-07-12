<?php

declare(strict_types=1);

namespace Modules\Finance\Contracts;

use Modules\Finance\DTOs\PostingResultData;

interface FinanceSourceReversalInterface
{
    public function reverseSource(
        int $tenantId,
        ?int $organizationUnitId,
        string $sourceModule,
        string $sourceType,
        int $sourceId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData;
}
