<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Events\TrainingDeleted;
use Modules\HR\Domain\Exceptions\TrainingNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;

class DeleteTraining
{
    public function __construct(private readonly TrainingRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $training = $this->repo->find($id);
        if (! $training) {
            throw new TrainingNotFoundException($id);
        }

        $tenantId = $training->getTenantId();
        $deleted  = $this->repo->delete($id);
        if ($deleted) {
            TrainingDeleted::dispatch($tenantId, $id);
        }

        return $deleted;
    }
}
