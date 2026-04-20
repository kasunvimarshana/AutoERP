<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

interface FindCycleCountServiceInterface
{
    public function find(int $tenantId, int $countId): mixed;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed;
}
