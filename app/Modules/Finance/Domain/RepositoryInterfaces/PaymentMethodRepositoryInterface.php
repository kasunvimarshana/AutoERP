<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\PaymentMethod;

interface PaymentMethodRepositoryInterface extends RepositoryInterface
{
    public function save(PaymentMethod $paymentMethod): PaymentMethod;
}
