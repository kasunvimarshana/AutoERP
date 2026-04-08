<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Dispatch\Application\Contracts\DeliverDispatchServiceInterface;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Events\DispatchDelivered;
use Modules\Dispatch\Domain\Exceptions\DispatchNotFoundException;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;

class DeliverDispatchService extends BaseService implements DeliverDispatchServiceInterface
{
    public function __construct(private readonly DispatchRepositoryInterface $dispatchRepository)
    {
        parent::__construct($dispatchRepository);
    }

    protected function handle(array $data): Dispatch
    {
        $id       = $data['id'];
        $dispatch = $this->dispatchRepository->find($id);

        if (! $dispatch) {
            throw new DispatchNotFoundException($id);
        }

        $dispatch->deliver($data['actual_delivery_date'] ?? null);

        $saved = $this->dispatchRepository->save($dispatch);
        $this->addEvent(new DispatchDelivered($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
