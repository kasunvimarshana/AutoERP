<?php

namespace Modules\Dispatch\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Dispatch\Application\Contracts\ProcessDispatchServiceInterface;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Events\DispatchProcessed;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Domain\ValueObjects\DispatchStatus;

class ProcessDispatchService implements ProcessDispatchServiceInterface
{
    public function __construct(private readonly DispatchRepositoryInterface $repository) {}

    public function execute(Dispatch $dispatch): Dispatch
    {
        if ($dispatch->status !== DispatchStatus::PENDING) {
            throw new \DomainException("Dispatch must be in pending status to process.");
        }
        $dispatch = $this->repository->update($dispatch, ['status' => DispatchStatus::PROCESSING]);
        Event::dispatch(new DispatchProcessed($dispatch->tenantId, $dispatch->id));
        return $dispatch;
    }
}
