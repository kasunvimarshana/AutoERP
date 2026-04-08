<?php

declare(strict_types=1);

namespace Modules\CRM\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface CustomerServiceInterface extends ServiceInterface
{
    /**
     * Create a new customer record.
     */
    public function createCustomer(array $data): mixed;

    /**
     * Update an existing customer by ID.
     */
    public function updateCustomer(string $id, array $data): mixed;
}
