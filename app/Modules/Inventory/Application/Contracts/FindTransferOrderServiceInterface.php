<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

interface FindTransferOrderServiceInterface
{
    public function find(int $tenantId, int $transferOrderId): mixed;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed;
}
