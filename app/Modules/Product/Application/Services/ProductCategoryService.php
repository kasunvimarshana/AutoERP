<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\ProductCategoryServiceInterface;
use Modules\Product\Domain\Contracts\Repositories\ProductCategoryRepositoryInterface;

class ProductCategoryService extends BaseService implements ProductCategoryServiceInterface
{
    public function __construct(ProductCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — creates a category.
     */
    protected function handle(array $data): mixed
    {
        return $this->repository->create($data);
    }
}
