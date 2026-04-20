<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindPaymentAllocationServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentAllocationRepositoryInterface;

class FindPaymentAllocationService extends BaseService implements FindPaymentAllocationServiceInterface
{
    public function __construct(private readonly PaymentAllocationRepositoryInterface $paymentAllocationRepository)
    {
        parent::__construct($paymentAllocationRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
