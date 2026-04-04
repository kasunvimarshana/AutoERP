<?php

namespace Modules\Dispatch\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Dispatch\Application\Contracts\DispatchShipmentServiceInterface;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Events\DispatchDispatched;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Domain\ValueObjects\DispatchStatus;

class DispatchShipmentService implements DispatchShipmentServiceInterface
{
    public function __construct(private readonly DispatchRepositoryInterface $repository) {}

    public function execute(Dispatch $dispatch, int $dispatchedBy): Dispatch
    {
        if ($dispatch->status !== DispatchStatus::PROCESSING) {
            throw new \DomainException("Dispatch must be in processing status before shipping.");
        }
        $dispatch = $this->repository->update($dispatch, [
            'status'        => DispatchStatus::DISPATCHED,
            'dispatched_at' => now(),
            'dispatched_by' => $dispatchedBy,
        ]);
        Event::dispatch(new DispatchDispatched($dispatch->tenantId, $dispatch->id));
        return $dispatch;
    }
}
