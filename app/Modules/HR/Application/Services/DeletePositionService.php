<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeletePositionServiceInterface;
use Modules\HR\Domain\Events\PositionDeleted;
use Modules\HR\Domain\Exceptions\PositionNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;

class DeletePositionService extends BaseService implements DeletePositionServiceInterface
{
    public function __construct(private readonly PositionRepositoryInterface $positionRepository)
    {
        parent::__construct($positionRepository);
    }

    protected function handle(array $data): bool
    {
        $id       = $data['id'];
        $position = $this->positionRepository->find($id);
        if (! $position) {
            throw new PositionNotFoundException($id);
        }
        $tenantId = $position->getTenantId();
        $deleted  = $this->positionRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new PositionDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
