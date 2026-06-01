<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnHeaderModel;

final class EloquentGdnHeaderRepository extends EloquentRepository implements GdnHeaderRepositoryInterface
{
    public function __construct(GdnHeaderModel $model)
    {
        parent::__construct($model);
    }
}
