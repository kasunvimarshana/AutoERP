<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemVariantAttributeValueRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeValueModel;

final class EloquentItemVariantAttributeValueRepository extends EloquentRepository implements ItemVariantAttributeValueRepositoryInterface
{
    public function __construct(ItemVariantAttributeValueModel $model)
    {
        parent::__construct($model);
    }
}
