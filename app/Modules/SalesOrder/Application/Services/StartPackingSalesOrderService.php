<?php

namespace Modules\SalesOrder\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\Events\SalesOrderPackingStarted;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Domain\ValueObjects\SalesOrderStatus;

class StartPackingSalesOrderService implements StartPackingSalesOrderServiceInterface
{
    public function __construct(private readonly SalesOrderRepositoryInterface $repository) {}

    public function execute(SalesOrder $so, int $packedBy): SalesOrder
    {
        if ($so->status !== SalesOrderStatus::PICKING) {
            throw new \DomainException("Sales order must be in picking status before packing can start.");
        }
        $so = $this->repository->update($so, [
            'status'    => SalesOrderStatus::PACKING,
            'packed_by' => $packedBy,
            'packed_at' => now(),
        ]);
        Event::dispatch(new SalesOrderPackingStarted($so->tenantId, $so->id));
        return $so;
    }
}
