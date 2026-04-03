<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\UoM\Domain\Entities\ProductUomSetting;

interface FindProductUomSettingServiceInterface extends ReadServiceInterface
{
    public function findByProduct(int $tenantId, int $productId): ?ProductUomSetting;
}
