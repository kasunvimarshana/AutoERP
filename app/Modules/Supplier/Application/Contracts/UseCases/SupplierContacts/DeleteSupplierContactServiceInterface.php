<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts\UseCases\SupplierContacts;

use Modules\Core\Application\Results\Result;

interface DeleteSupplierContactServiceInterface
{
    public function execute(int|string $id): Result;
}