<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\UoM\Domain\Entities\ProductUomSetting;

interface ProductUomSettingRepositoryInterface extends RepositoryInterface
{
    public function save(ProductUomSetting $setting): ProductUomSetting;

    public function findByProduct(int $tenantId, int $productId): ?ProductUomSetting;
}
