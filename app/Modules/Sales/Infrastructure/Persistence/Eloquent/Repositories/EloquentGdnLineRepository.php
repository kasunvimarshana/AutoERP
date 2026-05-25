<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnLineModel;

final class EloquentGdnLineRepository extends EloquentRepository implements GdnLineRepositoryInterface
{
    public function __construct(GdnLineModel $model)
    {
        parent::__construct($model);
    }
}