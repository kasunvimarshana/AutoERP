<?php
declare(strict_types=1);
namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Product\Application\Contracts\ProductComponentServiceInterface;
use Modules\Product\Domain\Entities\ProductComponent;
use Modules\Product\Domain\RepositoryInterfaces\ProductComponentRepositoryInterface;

class ProductComponentService implements ProductComponentServiceInterface
{
    public function __construct(
        private readonly ProductComponentRepositoryInterface $componentRepo,
    ) {}

    public function listForProduct(int $tenantId, int $parentProductId): array
    {
        return $this->componentRepo->findByParent($tenantId, $parentProductId);
    }

    /**
     * Replace all components for a combo product with the given list.
     * Uses a replace (delete+insert) strategy inside a transaction.
     *
     * @param array $components Each element: ['component_product_id'=>int,'quantity'=>float,'unit'=>string,'is_optional'=>bool]
     * @return ProductComponent[]
     */
    public function syncComponents(int $tenantId, int $parentProductId, array $components): array
    {
        return DB::transaction(function () use ($tenantId, $parentProductId, $components): array {
            $this->componentRepo->deleteByParent($tenantId, $parentProductId);
            $result = [];
            foreach ($components as $comp) {
                $result[] = $this->componentRepo->create(array_merge($comp, [
                    'tenant_id'         => $tenantId,
                    'parent_product_id' => $parentProductId,
                ]));
            }
            return $result;
        });
    }

    public function delete(int $id): void
    {
        $this->componentRepo->delete($id);
    }
}
