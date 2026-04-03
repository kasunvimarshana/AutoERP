<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;

interface PurchaseOrderRepositoryInterface extends RepositoryInterface
{
    public function save(PurchaseOrder $order): PurchaseOrder;
    public function findBySupplier(int $tenantId, int $supplierId): Collection;
    public function findByStatus(int $tenantId, string $status): Collection;
    public function findByReferenceNumber(int $tenantId, string $referenceNumber): ?PurchaseOrder;
}
