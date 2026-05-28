<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherMetadataValueRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherMetadataValueModel;

final class EloquentVoucherMetadataValueRepository extends EloquentRepository implements VoucherMetadataValueRepositoryInterface
{
    public function __construct(VoucherMetadataValueModel $model)
    {
        parent::__construct($model);
    }
}