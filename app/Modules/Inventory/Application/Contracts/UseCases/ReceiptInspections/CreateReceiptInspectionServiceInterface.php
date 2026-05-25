<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections;

use Modules\Core\Application\Results\Result;

interface CreateReceiptInspectionServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}