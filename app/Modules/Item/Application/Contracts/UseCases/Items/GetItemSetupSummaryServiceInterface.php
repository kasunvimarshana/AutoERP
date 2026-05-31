<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\Items;

use Modules\Core\Application\Results\Result;

interface GetItemSetupSummaryServiceInterface
{
    public function capabilities(int|string $id): Result;

    public function inventorySummary(int|string $id): Result;

    public function pricingReferences(int|string $id): Result;

    public function uomSetup(int|string $id): Result;

    /**
     * @param array<string, mixed> $payload
     */
    public function previewTypeSetup(array $payload): Result;
}
