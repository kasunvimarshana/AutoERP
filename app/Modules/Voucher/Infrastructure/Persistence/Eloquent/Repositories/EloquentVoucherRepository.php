<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherModel;

final class EloquentVoucherRepository extends EloquentRepository implements VoucherRepositoryInterface
{
    public function __construct(VoucherModel $model)
    {
        parent::__construct($model);
    }
}