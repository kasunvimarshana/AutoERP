<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class ProductService extends BaseService implements ProductServiceInterface
{
    public function __construct(ProductRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): mixed
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $product = $this->repository->create($data);
        $this->addEvent(new ProductCreated((int) $product->id));
        return $product;
    }

    public function create(ProductData $dto): mixed
    {
        return $this->execute($dto->toArray());
    }

    public function findBySku(string $sku, int $tenantId): mixed
    {
        return $this->repository->findBySku($sku, $tenantId);
    }

    public function findByBarcode(string $barcode, int $tenantId): mixed
    {
        return $this->repository->findByBarcode($barcode, $tenantId);
    }

    public function activate(int $id): mixed
    {
        return $this->update($id, ['status' => 'active']);
    }

    public function discontinue(int $id): mixed
    {
        return $this->update($id, ['status' => 'discontinued']);
    }
}
