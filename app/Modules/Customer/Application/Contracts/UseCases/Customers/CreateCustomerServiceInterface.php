<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\Customers;

use Modules\Core\Application\Results\Result;

interface CreateCustomerServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}