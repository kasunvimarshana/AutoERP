<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;

final class EloquentItemRepository extends EloquentRepository implements ItemRepositoryInterface
{
    public function __construct(ItemModel $model)
    {
        parent::__construct($model);
    }
}
