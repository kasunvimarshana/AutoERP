<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\StartCycleCountServiceInterface;
use Modules\Inventory\Domain\Entities\CycleCountHeader;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;

class StartCycleCountService implements StartCycleCountServiceInterface
{
    public function __construct(private readonly CycleCountRepositoryInterface $cycleCountRepository) {}

    public function execute(int $tenantId, int $countId): CycleCountHeader
    {
        $header = $this->cycleCountRepository->markInProgress($tenantId, $countId);
        if ($header === null) {
            throw new NotFoundException('CycleCount', $countId);
        }

        return $header;
    }
}
