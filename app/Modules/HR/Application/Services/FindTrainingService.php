<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\FindTrainingServiceInterface;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;

class FindTrainingService extends BaseService implements FindTrainingServiceInterface
{
    public function __construct(private readonly TrainingRepositoryInterface $trainingRepository)
    {
        parent::__construct($trainingRepository);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\Training>
     */
    public function getByStatus(string $status): array
    {
        return $this->trainingRepository->getByStatus($status);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
