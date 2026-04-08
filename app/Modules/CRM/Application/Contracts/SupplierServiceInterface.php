<?php

declare(strict_types=1);

namespace Modules\CRM\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface SupplierServiceInterface extends ServiceInterface
{
    /**
     * Create a new supplier record.
     */
    public function createSupplier(array $data): mixed;

    /**
     * Update an existing supplier by ID.
     */
    public function updateSupplier(string $id, array $data): mixed;
}
