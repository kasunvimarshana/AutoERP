<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherSettingRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherSettingModel;

final class EloquentVoucherSettingRepository extends EloquentRepository implements VoucherSettingRepositoryInterface
{
    public function __construct(VoucherSettingModel $model)
    {
        parent::__construct($model);
    }
}