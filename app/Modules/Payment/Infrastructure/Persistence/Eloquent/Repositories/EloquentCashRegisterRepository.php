<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\CashRegisterRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\CashRegisterModel;

final class EloquentCashRegisterRepository extends EloquentRepository implements CashRegisterRepositoryInterface
{
    public function __construct(CashRegisterModel $model)
    {
        parent::__construct($model);
    }
}