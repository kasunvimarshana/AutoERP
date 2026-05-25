<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines;

use Modules\Core\Application\Results\Result;

interface UpdatePurchaseOrderLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}