<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\ApproveStockReturnServiceInterface;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnApproved;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\ReturnStatus;

class ApproveStockReturnService implements ApproveStockReturnServiceInterface
{
    public function __construct(
        private readonly StockReturnRepositoryInterface $repository,
    ) {}

    public function execute(StockReturn $return, int $approvedBy): StockReturn
    {
        $updated = $this->repository->update($return, [
            'status'      => ReturnStatus::APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        Event::dispatch(new StockReturnApproved($updated->tenantId, $updated->id));

        return $updated;
    }
}
