<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherAllocationRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherAllocationModel;

final class EloquentVoucherAllocationRepository extends EloquentRepository implements VoucherAllocationRepositoryInterface
{
    public function __construct(VoucherAllocationModel $model)
    {
        parent::__construct($model);
    }
}