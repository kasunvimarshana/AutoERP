<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemAttributeRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeModel;

final class EloquentItemAttributeRepository extends EloquentRepository implements ItemAttributeRepositoryInterface
{
    public function __construct(ItemAttributeModel $model)
    {
        parent::__construct($model);
    }
}
