<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\Customers;

use Modules\Core\Application\Results\Result;

interface UpdateCustomerServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}