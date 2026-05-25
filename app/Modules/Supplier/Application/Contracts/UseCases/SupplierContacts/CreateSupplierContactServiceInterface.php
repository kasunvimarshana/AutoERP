<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\SupplierContacts;

use Modules\Core\Application\Results\Result;

interface CreateSupplierContactServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}