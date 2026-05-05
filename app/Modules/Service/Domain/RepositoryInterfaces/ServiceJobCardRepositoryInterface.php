<?php

declare(strict_types=1);

namespace Modules\Service\Domain\RepositoryInterfaces;

use Modules\Service\Domain\Entities\ServiceJobCard;

interface ServiceJobCardRepositoryInterface
{
    public function save(ServiceJobCard $jobCard): ServiceJobCard;

    public function findById(int $tenantId, int $id): ?ServiceJobCard;

    public function findByJobNumber(int $tenantId, string $jobNumber): ?ServiceJobCard;

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array;

    public function existsByJobNumber(int $tenantId, string $jobNumber): bool;
}
