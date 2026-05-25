<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\SupplierItems;

use Modules\Core\Application\Results\Result;

interface GetSupplierItemServiceInterface
{
    public function execute(int|string $id): Result;
}