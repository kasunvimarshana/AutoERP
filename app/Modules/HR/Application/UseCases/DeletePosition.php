<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Events\PositionDeleted;
use Modules\HR\Domain\Exceptions\PositionNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;

class DeletePosition
{
    public function __construct(private readonly PositionRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $position = $this->repo->find($id);
        if (! $position) {
            throw new PositionNotFoundException($id);
        }

        $tenantId = $position->getTenantId();
        $deleted  = $this->repo->delete($id);
        if ($deleted) {
            PositionDeleted::dispatch($id, $tenantId);
        }

        return $deleted;
    }
}
