<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeleteTrainingServiceInterface;
use Modules\HR\Domain\Events\TrainingDeleted;
use Modules\HR\Domain\Exceptions\TrainingNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;

class DeleteTrainingService extends BaseService implements DeleteTrainingServiceInterface
{
    public function __construct(private readonly TrainingRepositoryInterface $trainingRepository)
    {
        parent::__construct($trainingRepository);
    }

    protected function handle(array $data): bool
    {
        $id       = $data['id'];
        $training = $this->trainingRepository->find($id);
        if (! $training) {
            throw new TrainingNotFoundException($id);
        }

        $tenantId = $training->getTenantId();
        $deleted  = $this->trainingRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new TrainingDeleted($tenantId, $id));
        }

        return $deleted;
    }
}
