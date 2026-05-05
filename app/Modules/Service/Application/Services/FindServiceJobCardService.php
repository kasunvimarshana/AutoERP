<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Service\Application\Contracts\FindServiceJobCardServiceInterface;
use Modules\Service\Domain\Entities\ServiceJobCard;
use Modules\Service\Domain\Exceptions\ServiceJobCardNotFoundException;
use Modules\Service\Domain\RepositoryInterfaces\ServiceJobCardRepositoryInterface;

class FindServiceJobCardService implements FindServiceJobCardServiceInterface
{
    public function __construct(
        private readonly ServiceJobCardRepositoryInterface $jobCardRepository,
    ) {}

    public function findById(int $tenantId, int $id): ServiceJobCard
    {
        $jobCard = $this->jobCardRepository->findById($tenantId, $id);
        if ($jobCard === null) {
            throw new ServiceJobCardNotFoundException($id);
        }

        return $jobCard;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        return $this->jobCardRepository->paginate($tenantId, $filters, $perPage, $page);
    }
}
