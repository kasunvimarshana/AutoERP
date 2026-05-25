<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\CustomerVehicles;

use Modules\Core\Application\Results\Result;

interface CreateCustomerVehicleServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}