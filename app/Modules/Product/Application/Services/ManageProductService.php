<?php declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Product\Application\Contracts\ManageProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class ManageProductService implements ManageProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $tenantId = $data['tenant_id'];
            $slug = $data['slug'] ?? Str::slug($data['name']);

            $product = new Product(
                tenantId: (int) $tenantId,
                type: $data['type'],
                name: $data['name'],
                slug: $slug,
                baseUomId: (int) $data['base_uom_id'],
                imagePath: $data['image_path'] ?? null,
                categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
                brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
                orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
                sku: $data['sku'] ?? null,
                description: $data['description'] ?? null,
                purchaseUomId: isset($data['purchase_uom_id']) ? (int) $data['purchase_uom_id'] : null,
                salesUomId: isset($data['sales_uom_id']) ? (int) $data['sales_uom_id'] : null,
                taxGroupId: isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
                uomConversionFactor: $data['uom_conversion_factor'] ?? '1',
                isBatchTracked: (bool) ($data['is_batch_tracked'] ?? false),
                isLotTracked: (bool) ($data['is_lot_tracked'] ?? false),
                isSerialTracked: (bool) ($data['is_serial_tracked'] ?? false),
                valuationMethod: $data['valuation_method'] ?? 'fifo',
                standardCost: $data['standard_cost'] ?? null,
                incomeAccountId: isset($data['income_account_id']) ? (int) $data['income_account_id'] : null,
                cogsAccountId: isset($data['cogs_account_id']) ? (int) $data['cogs_account_id'] : null,
                inventoryAccountId: isset($data['inventory_account_id']) ? (int) $data['inventory_account_id'] : null,
                expenseAccountId: isset($data['expense_account_id']) ? (int) $data['expense_account_id'] : null,
                isActive: (bool) ($data['is_active'] ?? true),
            );

            $this->products->create($product);
            return $product;
        });
    }

    public function find(int $tenantId, string $id): Product
    {
        $product = $this->products->findById((int) $id);
        if (!$product || $product->getTenantId() !== $tenantId) {
            throw new \Exception('Product not found');
        }
        return $product;
    }

    public function list(int $tenantId, array $filters = []): array
    {
        return $this->products->findByTenant($tenantId, $filters);
    }

    public function update(int $tenantId, string $id, array $data): Product
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Product {
            $product = $this->find($tenantId, $id);

            $product->update(
                type: $data['type'] ?? $product->getType(),
                name: $data['name'] ?? $product->getName(),
                slug: $data['slug'] ?? $product->getSlug(),
                baseUomId: $data['base_uom_id'] ?? $product->getBaseUomId(),
                imagePath: $data['image_path'] ?? $product->getImagePath(),
                taxGroupId: $data['tax_group_id'] ?? $product->getTaxGroupId(),
                categoryId: $data['category_id'] ?? $product->getCategoryId(),
                brandId: $data['brand_id'] ?? $product->getBrandId(),
                orgUnitId: $data['org_unit_id'] ?? $product->getOrgUnitId(),
                sku: $data['sku'] ?? $product->getSku(),
                description: $data['description'] ?? $product->getDescription(),
                purchaseUomId: $data['purchase_uom_id'] ?? $product->getPurchaseUomId(),
                salesUomId: $data['sales_uom_id'] ?? $product->getSalesUomId(),
                uomConversionFactor: $data['uom_conversion_factor'] ?? $product->getUomConversionFactor(),
                isBatchTracked: (bool) ($data['is_batch_tracked'] ?? $product->isBatchTracked()),
                isLotTracked: (bool) ($data['is_lot_tracked'] ?? $product->isLotTracked()),
                isSerialTracked: (bool) ($data['is_serial_tracked'] ?? $product->isSerialTracked()),
                valuationMethod: $data['valuation_method'] ?? $product->getValuationMethod(),
                standardCost: $data['standard_cost'] ?? $product->getStandardCost(),
                incomeAccountId: $data['income_account_id'] ?? $product->getIncomeAccountId(),
                cogsAccountId: $data['cogs_account_id'] ?? $product->getCogsAccountId(),
                inventoryAccountId: $data['inventory_account_id'] ?? $product->getInventoryAccountId(),
                expenseAccountId: $data['expense_account_id'] ?? $product->getExpenseAccountId(),
                isActive: (bool) ($data['is_active'] ?? $product->isActive()),
            );

            $this->products->update($product);
            return $product;
        });
    }

    public function delete(int $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $product = $this->find($tenantId, $id);
            $this->products->delete((int) $id);
        });
    }
}
