<?php
namespace Modules\Configuration\Application\Services;

use Modules\Configuration\Application\Contracts\GetSettingServiceInterface;
use Modules\Configuration\Domain\Entities\SystemSetting;
use Modules\Configuration\Domain\RepositoryInterfaces\SystemSettingRepositoryInterface;

class GetSettingService implements GetSettingServiceInterface
{
    public function __construct(private readonly SystemSettingRepositoryInterface $repository) {}

    public function execute(int $tenantId, string $group, string $key): ?SystemSetting
    {
        return $this->repository->findByKey($tenantId, $group, $key);
    }
}
