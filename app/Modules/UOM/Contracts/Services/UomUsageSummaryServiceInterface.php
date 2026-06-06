<?php

declare(strict_types=1);

namespace Modules\UOM\Contracts\Services;

interface UomUsageSummaryServiceInterface
{
    /**
     * @return array<string, int>
     */
    public function summarize(int $unitId, int $tenantId): array;
}
