<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\Suppliers;

use Modules\Core\Application\Results\Result;

interface GetSupplierServiceInterface
{
    public function execute(int|string $id): Result;
}