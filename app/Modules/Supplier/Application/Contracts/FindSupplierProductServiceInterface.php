<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindSupplierProductServiceInterface extends ReadServiceInterface
{
    public function paginateBySupplier(int $tenantId, int $supplierId, int $perPage = 15, int $page = 1): mixed;
}
