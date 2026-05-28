<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherTypeRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherTypeModel;

final class EloquentVoucherTypeRepository extends EloquentRepository implements VoucherTypeRepositoryInterface
{
    public function __construct(VoucherTypeModel $model)
    {
        parent::__construct($model);
    }
}