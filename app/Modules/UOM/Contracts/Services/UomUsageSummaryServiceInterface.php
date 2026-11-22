<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\UOM\Contracts\Services;

interface UomUsageSummaryServiceInterface
{
    /**
     * @return array<string, int>
     */
    public function summarize(int $unitId, int $tenantId): array;
}
