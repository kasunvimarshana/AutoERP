<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses;

use Modules\Core\Application\Results\Result;

interface DeleteSupplierAddressesServiceInterface
{
    public function execute(int|string $id): Result;
}