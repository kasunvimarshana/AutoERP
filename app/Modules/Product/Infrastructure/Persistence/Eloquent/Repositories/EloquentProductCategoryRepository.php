<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Contracts\Repositories\ProductCategoryRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryModel;

class EloquentProductCategoryRepository extends EloquentRepository implements ProductCategoryRepositoryInterface
{
    public function __construct(ProductCategoryModel $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): mixed
    {
        return $this->model->newQuery()->where('code', $code)->first();
    }
}
