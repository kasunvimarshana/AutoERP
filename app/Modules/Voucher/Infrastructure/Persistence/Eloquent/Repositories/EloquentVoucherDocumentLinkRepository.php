<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherDocumentLinkRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherDocumentLinkModel;

final class EloquentVoucherDocumentLinkRepository extends EloquentRepository implements VoucherDocumentLinkRepositoryInterface
{
    public function __construct(VoucherDocumentLinkModel $model)
    {
        parent::__construct($model);
    }
}