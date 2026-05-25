<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemCategoryRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemCategoryModel;

final class EloquentItemCategoryRepository extends EloquentRepository implements ItemCategoryRepositoryInterface
{
    public function __construct(ItemCategoryModel $model)
    {
        parent::__construct($model);
    }
}
