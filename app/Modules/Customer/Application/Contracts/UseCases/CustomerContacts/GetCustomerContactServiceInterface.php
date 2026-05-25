<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\CustomerContacts;

use Modules\Core\Application\Results\Result;

interface GetCustomerContactServiceInterface
{
    public function execute(int|string $id): Result;
}