<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherPaymentLinkRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherPaymentLinkModel;

final class EloquentVoucherPaymentLinkRepository extends EloquentRepository implements VoucherPaymentLinkRepositoryInterface
{
    public function __construct(VoucherPaymentLinkModel $model)
    {
        parent::__construct($model);
    }
}