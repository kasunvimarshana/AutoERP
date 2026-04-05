<?php
declare(strict_types=1);
namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\ProductComponent;

interface ProductComponentServiceInterface
{
    public function listForProduct(int $tenantId, int $parentProductId): array;
    public function syncComponents(int $tenantId, int $parentProductId, array $components): array;
    public function delete(int $id): void;
}
