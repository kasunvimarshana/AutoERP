<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;

final class EloquentPaymentMethodRepository extends EloquentRepository implements PaymentMethodRepositoryInterface
{
    public function __construct(PaymentMethodModel $model)
    {
        parent::__construct($model);
    }
}