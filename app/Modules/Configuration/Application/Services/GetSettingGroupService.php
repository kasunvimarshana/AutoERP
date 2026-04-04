<?php
namespace Modules\Configuration\Application\Services;

use Modules\Configuration\Application\Contracts\GetSettingGroupServiceInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\SystemSettingRepositoryInterface;

class GetSettingGroupService implements GetSettingGroupServiceInterface
{
    public function __construct(private readonly SystemSettingRepositoryInterface $repository) {}

    public function execute(int $tenantId, string $group): array
    {
        return $this->repository->findByGroup($tenantId, $group);
    }
}
