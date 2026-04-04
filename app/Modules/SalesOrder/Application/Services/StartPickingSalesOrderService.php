<?php

namespace Modules\SalesOrder\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\SalesOrder\Application\Contracts\StartPickingSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderPickingStarted;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Domain\ValueObjects\SalesOrderStatus;

class StartPickingSalesOrderService implements StartPickingSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $repository) {}

    public function execute(SalesOrder $so, int $pickedBy): SalesOrder
    {
        if ($so->status !== SalesOrderStatus::CONFIRMED) {
            throw new \DomainException("Sales order must be confirmed before picking can start.");
        }
        $so = $this->repository->update($so, [
            'status'    => SalesOrderStatus::PICKING,
            'picked_by' => $pickedBy,
            'picked_at' => now(),
        ]);
        Event::dispatch(new SalesOrderPickingStarted($so->tenantId, $so->id));
        return $so;
    }
}
