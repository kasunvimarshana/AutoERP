<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\RepositoryInterfaces;

use Modules\ServiceCenter\Domain\Entities\ServiceOrder;

interface ServiceOrderRepositoryInterface
{
    public function create(ServiceOrder $order): void;

    public function findById(string $id): ?ServiceOrder;

    public function findByOrderNumber(string $tenantId, string $orderNumber): ?ServiceOrder;

    public function getByTenant(string $tenantId, int $page = 1, int $limit = 50): array;

    public function getByAsset(string $tenantId, string $assetId, int $page = 1, int $limit = 50): array;

    public function getByStatus(string $tenantId, string $status, int $page = 1, int $limit = 50): array;

    public function update(ServiceOrder $order): void;
}
