<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\ReceiptInspections;

use Modules\Core\Application\Results\Result;

interface DeleteReceiptInspectionServiceInterface
{
    public function execute(int|string $id): Result;
}