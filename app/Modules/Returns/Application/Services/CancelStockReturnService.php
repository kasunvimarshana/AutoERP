<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\CancelStockReturnServiceInterface;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnCancelled;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\ReturnStatus;

class CancelStockReturnService implements CancelStockReturnServiceInterface
{
    public function __construct(
        private readonly StockReturnRepositoryInterface $repository,
    ) {}

    public function execute(StockReturn $return): StockReturn
    {
        if ($return->status === ReturnStatus::COMPLETED) {
            throw new \DomainException('Cannot cancel a completed stock return.');
        }

        $updated = $this->repository->update($return, [
            'status' => ReturnStatus::CANCELLED,
        ]);

        Event::dispatch(new StockReturnCancelled($updated->tenantId, $updated->id));

        return $updated;
    }
}
