<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\CycleCountHeader;

interface CompleteCycleCountServiceInterface
{
    /**
     * @param  array<int, array{line_id:int, counted_qty:string}>  $countedLines
     */
    public function execute(int $tenantId, int $countId, int $approvedByUserId, array $countedLines): CycleCountHeader;
}
