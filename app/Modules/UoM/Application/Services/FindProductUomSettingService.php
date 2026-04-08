<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\FindProductUomSettingServiceInterface;
use Modules\UoM\Domain\Entities\ProductUomSetting;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;

class FindProductUomSettingService extends BaseService implements FindProductUomSettingServiceInterface
{
    public function __construct(private readonly ProductUomSettingRepositoryInterface $settingRepository)
    {
        parent::__construct($settingRepository);
    }

    public function findByProduct(int $tenantId, int $productId): ?ProductUomSetting
    {
        return $this->settingRepository->findByProduct($tenantId, $productId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
