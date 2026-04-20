<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\ValuationConfig;

/**
 * CRUD operations for per-scope valuation configurations.
 */
interface ManageValuationConfigServiceInterface
{
    public function create(array $data): ValuationConfig;

    public function update(int $tenantId, int $id, array $data): ValuationConfig;

    public function delete(int $tenantId, int $id): void;

    public function find(int $tenantId, int $id): ValuationConfig;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed;
}
