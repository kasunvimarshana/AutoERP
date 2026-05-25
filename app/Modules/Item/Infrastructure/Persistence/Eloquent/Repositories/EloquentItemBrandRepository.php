<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemBrandRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemBrandModel;

final class EloquentItemBrandRepository extends EloquentRepository implements ItemBrandRepositoryInterface
{
    public function __construct(ItemBrandModel $model)
    {
        parent::__construct($model);
    }
}
