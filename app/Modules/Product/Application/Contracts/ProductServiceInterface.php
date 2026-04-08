<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Product\Application\DTOs\ProductData;

interface ProductServiceInterface
{
    public function create(ProductData $dto): mixed;
    public function findBySku(string $sku, int $tenantId): mixed;
    public function findByBarcode(string $barcode, int $tenantId): mixed;
    public function activate(int $id): mixed;
    public function discontinue(int $id): mixed;
}
