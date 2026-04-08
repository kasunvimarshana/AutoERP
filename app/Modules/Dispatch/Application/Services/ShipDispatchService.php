<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Dispatch\Application\Contracts\ShipDispatchServiceInterface;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Events\DispatchShipped;
use Modules\Dispatch\Domain\Exceptions\DispatchNotFoundException;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;

class ShipDispatchService extends BaseService implements ShipDispatchServiceInterface
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

        $dispatch->ship((int) $data['shipped_by'], $data['tracking_number'] ?? null);

        $saved = $this->dispatchRepository->save($dispatch);
        $this->addEvent(new DispatchShipped($saved->getId(), $saved->getShippedBy()));

        return $saved;
    }
}
