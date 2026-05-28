<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Voucher\Application\Repositories\VoucherMetadataDefinitionRepositoryInterface;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Models\VoucherMetadataDefinitionModel;

final class EloquentVoucherMetadataDefinitionRepository extends EloquentRepository implements VoucherMetadataDefinitionRepositoryInterface
{
    public function __construct(VoucherMetadataDefinitionModel $model)
    {
        parent::__construct($model);
    }
}