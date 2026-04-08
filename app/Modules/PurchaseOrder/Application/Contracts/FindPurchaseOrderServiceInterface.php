<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindPurchaseOrderServiceInterface extends ReadServiceInterface
{
    public function findBySupplier(int $tenantId, int $supplierId): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
}
