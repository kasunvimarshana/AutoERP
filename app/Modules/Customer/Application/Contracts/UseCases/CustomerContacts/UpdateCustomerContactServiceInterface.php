<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Contracts\UseCases\CustomerContacts;

use Modules\Core\Application\Results\Result;

interface UpdateCustomerContactServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}