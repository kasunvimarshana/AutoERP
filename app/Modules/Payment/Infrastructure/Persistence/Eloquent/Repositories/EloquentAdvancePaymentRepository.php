<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\AdvancePaymentModel;

final class EloquentAdvancePaymentRepository extends EloquentRepository implements AdvancePaymentRepositoryInterface
{
    public function __construct(AdvancePaymentModel $model)
    {
        parent::__construct($model);
    }
}