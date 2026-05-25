<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\CustomerVehicles;

use Modules\Core\Application\Results\Result;

interface DeleteCustomerVehicleServiceInterface
{
    public function execute(int|string $id): Result;
}