<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\RecurringVoucherRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\RecurringVoucherModel;

final class EloquentRecurringVoucherRepository extends EloquentRepository implements RecurringVoucherRepositoryInterface
{
    public function __construct(RecurringVoucherModel $model)
    {
        parent::__construct($model);
    }
}