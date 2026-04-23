<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\CreatePerformanceCycleServiceInterface;
use Modules\HR\Application\DTOs\PerformanceCycleData;
use Modules\HR\Domain\Entities\PerformanceCycle;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceCycleRepositoryInterface;

class CreatePerformanceCycleService extends BaseService implements CreatePerformanceCycleServiceInterface
{
    public function __construct(
        private readonly PerformanceCycleRepositoryInterface $cycleRepository,
    ) {
        parent::__construct($this->cycleRepository);
    }

    protected function handle(array $data): PerformanceCycle
    {
        $dto = PerformanceCycleData::fromArray($data);

        $now = new \DateTimeImmutable;
        $cycle = new PerformanceCycle(
            tenantId: $dto->tenantId,
            name: $dto->name,
            periodStart: new \DateTimeImmutable($dto->periodStart),
            periodEnd: new \DateTimeImmutable($dto->periodEnd),
            isActive: $dto->isActive,
            metadata: $dto->metadata,
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->cycleRepository->save($cycle);
    }
}
