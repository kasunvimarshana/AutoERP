<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\PaymentAllocation;

interface PaymentAllocationRepositoryInterface extends RepositoryInterface
{
    public function save(PaymentAllocation $paymentAllocation): PaymentAllocation;
}
