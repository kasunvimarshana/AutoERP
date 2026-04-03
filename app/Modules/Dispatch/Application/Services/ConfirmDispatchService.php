<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Dispatch\Application\Contracts\ConfirmDispatchServiceInterface;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\Events\DispatchConfirmed;
use Modules\Dispatch\Domain\Exceptions\DispatchNotFoundException;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;

class ConfirmDispatchService extends BaseService implements ConfirmDispatchServiceInterface
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

        $dispatch->confirm((int) $data['confirmed_by']);

        $saved = $this->dispatchRepository->save($dispatch);
        $this->addEvent(new DispatchConfirmed($saved->getId(), $saved->getConfirmedBy()));

        return $saved;
    }
}
