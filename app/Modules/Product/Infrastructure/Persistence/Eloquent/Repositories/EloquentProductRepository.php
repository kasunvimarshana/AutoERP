<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Sku;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;

class EloquentProductRepository extends EloquentRepository implements ProductRepositoryInterface
{
    public function __construct(ProductModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductModel $model): Product => $this->mapModelToDomainEntity($model));
    }

    public function findBySku(int $tenantId, string $sku): ?Product
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('sku', $sku)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenant(int $tenantId): Collection
    {
        return $this->toDomainCollection($this->model->where('tenant_id', $tenantId)->get());
    }

    public function save(Product $product): Product
    {
        $data = [
            'tenant_id'   => $product->getTenantId(),
            'sku'         => $product->getSku()->value(),
            'name'        => $product->getName(),
            'description' => $product->getDescription(),
            'price'       => $product->getPrice()->getAmount(),
            'currency'    => $product->getPrice()->getCurrency(),
            'category'    => $product->getCategory(),
            'status'      => $product->getStatus(),
            'attributes'  => $product->getAttributes(),
            'metadata'    => $product->getMetadata(),
        ];

        if ($product->getId()) {
            $model = $this->update($product->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductModel $model */
        $model->load('images');

        return $this->toDomainEntity($model);
    }

    public function find($id, array $columns = ['*']): ?Product
    {
        $this->with(['images']);

        return parent::find($id, $columns);
    }

    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        $this->with(['images']);

        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    private function mapModelToDomainEntity(ProductModel $model): Product
    {
        $product = new Product(
            tenantId: $model->tenant_id,
            sku: new Sku($model->sku),
            name: $model->name,
            price: new Money((float) $model->price, $model->currency ?? 'USD'),
            description: $model->description,
            category: $model->category,
            status: $model->status,
            attributes: $model->attributes,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );

        if ($model->relationLoaded('images')) {
            $images = $model->images->map(fn (ProductImageModel $img) => $this->mapImageModelToDomainEntity($img));
            $product->setImages($images);
        }

        return $product;
    }

    private function mapImageModelToDomainEntity(ProductImageModel $model): ProductImage
    {
        return new ProductImage(
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            uuid: $model->uuid,
            name: $model->name,
            filePath: $model->file_path,
            mimeType: $model->mime_type,
            size: $model->size,
            sortOrder: $model->sort_order,
            isPrimary: (bool) $model->is_primary,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
