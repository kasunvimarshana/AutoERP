<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Domain\Contracts\Repositories\ProductVariantRepositoryInterface;

class ProductVariantService extends BaseService implements ProductVariantServiceInterface
{
    public function __construct(ProductVariantRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — creates a variant.
     */
    protected function handle(array $data): mixed
    {
        return $this->repository->create($data);
    }
}
