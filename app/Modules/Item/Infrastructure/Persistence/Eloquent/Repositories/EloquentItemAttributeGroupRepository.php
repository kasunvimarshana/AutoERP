<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemAttributeGroupRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeGroupModel;

final class EloquentItemAttributeGroupRepository extends EloquentRepository implements ItemAttributeGroupRepositoryInterface
{
    public function __construct(ItemAttributeGroupModel $model)
    {
        parent::__construct($model);
    }
}
