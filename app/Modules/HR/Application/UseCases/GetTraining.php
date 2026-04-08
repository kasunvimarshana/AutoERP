<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\Training;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;

class GetTraining
{
    public function __construct(private readonly TrainingRepositoryInterface $repo) {}

    public function execute(int $id): ?Training
    {
        return $this->repo->find($id);
    }
}
