<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemIdentifierRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemIdentifierModel;

final class EloquentItemIdentifierRepository extends EloquentRepository implements ItemIdentifierRepositoryInterface
{
    public function __construct(ItemIdentifierModel $model)
    {
        parent::__construct($model);
    }
}
