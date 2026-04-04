<?php

namespace Modules\Dispatch\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Dispatch\Application\Contracts\MarkDeliveredServiceInterface;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Events\DispatchDelivered;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Domain\ValueObjects\DispatchStatus;

class MarkDeliveredService implements MarkDeliveredServiceInterface
{
    public function __construct(private readonly DispatchRepositoryInterface $repository) {}

    public function execute(Dispatch $dispatch): Dispatch
    {
        if ($dispatch->status !== DispatchStatus::DISPATCHED) {
            throw new \DomainException("Dispatch must be in dispatched status to mark as delivered.");
        }
        $dispatch = $this->repository->update($dispatch, [
            'status'       => DispatchStatus::DELIVERED,
            'delivered_at' => now(),
        ]);
        Event::dispatch(new DispatchDelivered($dispatch->tenantId, $dispatch->id));
        return $dispatch;
    }
}
