<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherFinancePostingLinkRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherFinancePostingLinkModel;

final class EloquentVoucherFinancePostingLinkRepository extends EloquentRepository implements VoucherFinancePostingLinkRepositoryInterface
{
    public function __construct(VoucherFinancePostingLinkModel $model)
    {
        parent::__construct($model);
    }
}