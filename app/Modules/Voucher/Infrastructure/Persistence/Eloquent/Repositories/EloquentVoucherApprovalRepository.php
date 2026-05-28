<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherApprovalRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherApprovalModel;

final class EloquentVoucherApprovalRepository extends EloquentRepository implements VoucherApprovalRepositoryInterface
{
    public function __construct(VoucherApprovalModel $model)
    {
        parent::__construct($model);
    }
}