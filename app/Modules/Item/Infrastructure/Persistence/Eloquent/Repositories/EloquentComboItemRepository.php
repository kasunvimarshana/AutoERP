<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ComboItemRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ComboItemModel;

final class EloquentComboItemRepository extends EloquentRepository implements ComboItemRepositoryInterface
{
    public function __construct(ComboItemModel $model)
    {
        parent::__construct($model);
    }
}
