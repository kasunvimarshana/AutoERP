<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeletePaymentAllocationServiceInterface;
use Modules\Finance\Domain\Exceptions\PaymentAllocationNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentAllocationRepositoryInterface;

class DeletePaymentAllocationService extends BaseService implements DeletePaymentAllocationServiceInterface
{
    public function __construct(private readonly PaymentAllocationRepositoryInterface $paymentAllocationRepository)
    {
        parent::__construct($paymentAllocationRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->paymentAllocationRepository->find($id)) {
            throw new PaymentAllocationNotFoundException($id);
        }

        return $this->paymentAllocationRepository->delete($id);
    }
}
