<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles;

use Modules\Core\Application\Results\Result;

interface DeleteSupplierVehicleServiceInterface
{
    public function execute(int|string $id): Result;
}