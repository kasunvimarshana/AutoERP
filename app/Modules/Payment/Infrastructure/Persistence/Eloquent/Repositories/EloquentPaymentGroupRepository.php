<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\PaymentGroupRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentGroupModel;

final class EloquentPaymentGroupRepository extends EloquentRepository implements PaymentGroupRepositoryInterface
{
    public function __construct(PaymentGroupModel $model)
    {
        parent::__construct($model);
    }
}