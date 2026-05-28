<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\PurchaseSettingRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseSettingModel;

final class EloquentPurchaseSettingRepository extends EloquentRepository implements PurchaseSettingRepositoryInterface
{
    public function __construct(PurchaseSettingModel $model)
    {
        parent::__construct($model);
    }
}
