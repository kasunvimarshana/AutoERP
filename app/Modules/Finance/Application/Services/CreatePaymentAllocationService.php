<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreatePaymentAllocationServiceInterface;
use Modules\Finance\Application\DTOs\PaymentAllocationData;
use Modules\Finance\Domain\Entities\PaymentAllocation;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentAllocationRepositoryInterface;

class CreatePaymentAllocationService extends BaseService implements CreatePaymentAllocationServiceInterface
{
    public function __construct(private readonly PaymentAllocationRepositoryInterface $paymentAllocationRepository)
    {
        parent::__construct($paymentAllocationRepository);
    }

    protected function handle(array $data): PaymentAllocation
    {
        $dto = PaymentAllocationData::fromArray($data);

        $pa = new PaymentAllocation(
            paymentId: $dto->payment_id,
            invoiceType: $dto->invoice_type,
            invoiceId: $dto->invoice_id,
            allocatedAmount: $dto->allocated_amount,
            tenantId: $dto->tenant_id,
        );

        return $this->paymentAllocationRepository->save($pa);
    }
}
