<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Domain\Contracts;

use Modules\Ecommerce\Domain\Entities\StorefrontOrder;

interface StorefrontOrderRepositoryInterface
{
    public function save(StorefrontOrder $order): StorefrontOrder;

    public function findById(int $id, int $tenantId): ?StorefrontOrder;

    public function findAll(int $tenantId, int $page, int $perPage): array;

    public function saveLines(int $orderId, array $lines): array;

    public function findLines(int $orderId, int $tenantId): array;

    public function delete(int $id, int $tenantId): void;
}
