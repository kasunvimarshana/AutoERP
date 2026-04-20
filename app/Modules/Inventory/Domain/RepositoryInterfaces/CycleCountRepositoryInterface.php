<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\CycleCountHeader;

interface CycleCountRepositoryInterface
{
    public function create(CycleCountHeader $header): CycleCountHeader;

    public function findById(int $tenantId, int $countId): ?CycleCountHeader;

    public function paginate(int $tenantId, int $perPage, int $page): mixed;

    public function markInProgress(int $tenantId, int $countId): ?CycleCountHeader;

    /**
     * @param  array<int, array{line_id:int, counted_qty:string, adjustment_movement_id:?int}>  $lineUpdates
     */
    public function complete(int $tenantId, int $countId, array $lineUpdates, int $approvedByUserId): ?CycleCountHeader;
}
