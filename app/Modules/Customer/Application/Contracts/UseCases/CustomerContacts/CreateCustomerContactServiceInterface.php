<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\CustomerContacts;

use Modules\Core\Application\Results\Result;

interface CreateCustomerContactServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}