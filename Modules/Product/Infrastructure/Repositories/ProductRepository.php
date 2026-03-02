<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Infrastructure\Models\ProductModel;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    protected function model(): string
    {
        return ProductModel::class;
    }

    public function findById(int $id, int $tenantId): ?Product
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySku(string $sku, int $tenantId): ?Product
    {
        $model = $this->newQuery()
            ->where('sku', $sku)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByBarcode(string $barcode, int $tenantId): ?Product
    {
        $model = $this->newQuery()
            ->where('barcode', $barcode)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (ProductModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(Product $product): Product
    {
        if ($product->id !== null) {
            $model = $this->newQuery()
                ->where('id', $product->id)
                ->where('tenant_id', $product->tenantId)
                ->firstOrFail();
        } else {
            $model = new ProductModel;
        }

        $model->tenant_id = $product->tenantId;
        $model->sku = $product->sku;
        $model->name = $product->name;
        $model->description = $product->description;
        $model->type = $product->type;
        $model->uom = $product->uom;
        $model->buying_uom = $product->buyingUom;
        $model->selling_uom = $product->sellingUom;
        $model->costing_method = $product->costingMethod;
        $model->cost_price = $product->costPrice;
        $model->sale_price = $product->salePrice;
        $model->barcode = $product->barcode;
        $model->status = $product->status;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Product with ID {$id} not found.");
        }

        $model->delete();
    }

    private function toDomain(ProductModel $model): Product
    {
        return new Product(
            id: $model->id,
            tenantId: $model->tenant_id,
            sku: $model->sku,
            name: $model->name,
            description: $model->description,
            type: $model->type,
            uom: $model->uom,
            buyingUom: $model->buying_uom,
            sellingUom: $model->selling_uom,
            costingMethod: $model->costing_method,
            costPrice: $model->cost_price,
            salePrice: $model->sale_price,
            barcode: $model->barcode,
            status: $model->status,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
