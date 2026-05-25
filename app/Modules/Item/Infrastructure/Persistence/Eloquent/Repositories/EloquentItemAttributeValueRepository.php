<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemAttributeValueRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeValueModel;

final class EloquentItemAttributeValueRepository extends EloquentRepository implements ItemAttributeValueRepositoryInterface
{
    public function __construct(ItemAttributeValueModel $model)
    {
        parent::__construct($model);
    }
}
