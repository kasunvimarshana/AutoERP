<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses;

use Modules\Core\Application\Results\Result;

interface CreateSupplierAddressesServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}