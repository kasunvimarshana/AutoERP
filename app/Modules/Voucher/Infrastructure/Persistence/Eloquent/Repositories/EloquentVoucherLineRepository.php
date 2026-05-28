<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherLineRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherLineModel;

final class EloquentVoucherLineRepository extends EloquentRepository implements VoucherLineRepositoryInterface
{
    public function __construct(VoucherLineModel $model)
    {
        parent::__construct($model);
    }
}