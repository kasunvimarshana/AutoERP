<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;

final class EloquentUomConversionRepository extends EloquentRepository implements UomConversionRepositoryInterface
{
    public function __construct(UomConversionModel $model)
    {
        parent::__construct($model);
    }
}