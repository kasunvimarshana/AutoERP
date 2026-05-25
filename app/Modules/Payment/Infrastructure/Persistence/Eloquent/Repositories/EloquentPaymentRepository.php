<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentModel;

final class EloquentPaymentRepository extends EloquentRepository implements PaymentRepositoryInterface
{
    public function __construct(PaymentModel $model)
    {
        parent::__construct($model);
    }
}