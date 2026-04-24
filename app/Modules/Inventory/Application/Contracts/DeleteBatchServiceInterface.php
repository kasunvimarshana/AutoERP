<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

interface DeleteBatchServiceInterface
{
    public function execute(array $data): bool;
}
