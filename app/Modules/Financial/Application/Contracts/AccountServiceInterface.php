<?php

declare(strict_types=1);

namespace Modules\Financial\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface AccountServiceInterface extends ServiceInterface
{
    /**
     * Create a new chart-of-accounts entry.
     */
    public function createAccount(array $data): mixed;

    /**
     * Update an existing account.
     */
    public function updateAccount(string $id, array $data): mixed;
}
