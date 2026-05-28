<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherStatusHistoryRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherStatusHistoryModel;

final class EloquentVoucherStatusHistoryRepository extends EloquentRepository implements VoucherStatusHistoryRepositoryInterface
{
    public function __construct(VoucherStatusHistoryModel $model)
    {
        parent::__construct($model);
    }
}