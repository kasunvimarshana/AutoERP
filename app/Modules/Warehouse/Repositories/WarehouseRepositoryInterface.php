<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Warehouse\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;

interface WarehouseRepositoryInterface extends RepositoryPortInterface
{
    public function countByTenant(int $tenantId): int;
}
