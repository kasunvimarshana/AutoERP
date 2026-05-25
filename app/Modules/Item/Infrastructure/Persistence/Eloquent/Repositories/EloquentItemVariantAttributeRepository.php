<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemVariantAttributeRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeModel;

final class EloquentItemVariantAttributeRepository extends EloquentRepository implements ItemVariantAttributeRepositoryInterface
{
    public function __construct(ItemVariantAttributeModel $model)
    {
        parent::__construct($model);
    }
}
