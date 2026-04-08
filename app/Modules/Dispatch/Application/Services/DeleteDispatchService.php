<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Dispatch\Application\Contracts\DeleteDispatchServiceInterface;
use Modules\Dispatch\Domain\Events\DispatchDeleted;
use Modules\Dispatch\Domain\Exceptions\DispatchNotFoundException;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;

class DeleteDispatchService extends BaseService implements DeleteDispatchServiceInterface
{
    public function __construct(private readonly DispatchRepositoryInterface $dispatchRepository)
    {
        parent::__construct($dispatchRepository);
    }

    protected function handle(array $data): bool
    {
        $id       = $data['id'];
        $dispatch = $this->dispatchRepository->find($id);

        if (! $dispatch) {
            throw new DispatchNotFoundException($id);
        }

        $this->addEvent(new DispatchDeleted($dispatch->getId(), $dispatch->getTenantId()));

        return $this->dispatchRepository->delete($id);
    }
}
