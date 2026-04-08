<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Application\DTOs\ProductVariantData;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class ProductVariantService extends BaseService implements ProductVariantServiceInterface
{
    public function __construct(ProductVariantRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): mixed
    {
        return $this->repository->create($data);
    }

    public function create(ProductVariantData $dto): mixed
    {
        return $this->execute($dto->toArray());
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->repository->where('product_id', $productId)->get();
    }
}
