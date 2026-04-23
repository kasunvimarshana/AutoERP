<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdatePerformanceCycleServiceInterface;
use Modules\HR\Application\DTOs\PerformanceCycleData;
use Modules\HR\Domain\Entities\PerformanceCycle;
use Modules\HR\Domain\Exceptions\PerformanceCycleNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceCycleRepositoryInterface;

class UpdatePerformanceCycleService extends BaseService implements UpdatePerformanceCycleServiceInterface
{
    public function __construct(
        private readonly PerformanceCycleRepositoryInterface $cycleRepository,
    ) {
        parent::__construct($this->cycleRepository);
    }

    protected function handle(array $data): PerformanceCycle
    {
        $id = (int) ($data['id'] ?? 0);
        $cycle = $this->cycleRepository->find($id);

        if ($cycle === null) {
            throw new PerformanceCycleNotFoundException($id);
        }

        $dto = PerformanceCycleData::fromArray($data);

        if ($cycle->getTenantId() !== $dto->tenantId) {
            throw new PerformanceCycleNotFoundException($id);
        }

        $updated = new PerformanceCycle(
            tenantId: $cycle->getTenantId(),
            name: $dto->name,
            periodStart: new \DateTimeImmutable($dto->periodStart),
            periodEnd: new \DateTimeImmutable($dto->periodEnd),
            isActive: $dto->isActive,
            metadata: $dto->metadata,
            createdAt: $cycle->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $cycle->getId(),
        );

        return $this->cycleRepository->save($updated);
    }
}
