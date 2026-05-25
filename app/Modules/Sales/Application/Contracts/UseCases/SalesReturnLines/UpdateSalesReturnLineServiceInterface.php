<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesReturnLines;

use Modules\Core\Application\Results\Result;

interface UpdateSalesReturnLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}