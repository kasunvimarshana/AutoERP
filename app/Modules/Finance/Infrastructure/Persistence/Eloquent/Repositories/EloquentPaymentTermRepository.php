<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Finance\Application\Repositories\PaymentTermRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentTermModel;

final class EloquentPaymentTermRepository extends FinanceRepository implements PaymentTermRepositoryInterface
{
    public function __construct(PaymentTermModel $model)
    {
        parent::__construct($model);
    }
}
