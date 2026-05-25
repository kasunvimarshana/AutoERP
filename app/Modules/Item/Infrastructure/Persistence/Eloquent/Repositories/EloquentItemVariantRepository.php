<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemVariantRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantModel;

final class EloquentItemVariantRepository extends EloquentRepository implements ItemVariantRepositoryInterface
{
    public function __construct(ItemVariantModel $model)
    {
        parent::__construct($model);
    }
}
