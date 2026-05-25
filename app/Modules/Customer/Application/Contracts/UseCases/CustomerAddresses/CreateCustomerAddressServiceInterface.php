<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\CustomerAddresses;

use Modules\Core\Application\Results\Result;

interface CreateCustomerAddressServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}