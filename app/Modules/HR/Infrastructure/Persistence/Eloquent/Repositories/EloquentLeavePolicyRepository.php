<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\LeavePolicyRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeavePolicyModel;

final class EloquentLeavePolicyRepository extends EloquentRepository implements LeavePolicyRepositoryInterface
{
    public function __construct(LeavePolicyModel $model)
    {
        parent::__construct($model);
    }
}