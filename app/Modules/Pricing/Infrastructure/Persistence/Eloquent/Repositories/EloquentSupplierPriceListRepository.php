<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\SupplierPriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\SupplierPriceListModel;

final class EloquentSupplierPriceListRepository extends EloquentRepository implements SupplierPriceListRepositoryInterface
{
    public function __construct(SupplierPriceListModel $model)
    {
        parent::__construct($model);
    }
}