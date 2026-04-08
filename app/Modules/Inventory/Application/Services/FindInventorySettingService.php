<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\FindInventorySettingServiceInterface;
use Modules\Inventory\Domain\Entities\InventorySetting;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;

class FindInventorySettingService extends BaseService implements FindInventorySettingServiceInterface
{
    public function __construct(private readonly InventorySettingRepositoryInterface $settingRepository)
    {
        parent::__construct($settingRepository);
    }

    public function findByTenant(int $tenantId): ?InventorySetting
    {
        return $this->settingRepository->findByTenant($tenantId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
