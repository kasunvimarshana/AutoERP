<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\PaymentAllocation;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentAllocationRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentAllocationModel;

class EloquentPaymentAllocationRepository extends EloquentRepository implements PaymentAllocationRepositoryInterface
{
    public function __construct(PaymentAllocationModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PaymentAllocationModel $m): PaymentAllocation => $this->mapToDomain($m));
    }

    public function save(PaymentAllocation $pa): PaymentAllocation
    {
        $data = [
            'tenant_id' => $pa->getTenantId(),
            'payment_id' => $pa->getPaymentId(),
            'invoice_type' => $pa->getInvoiceType(),
            'invoice_id' => $pa->getInvoiceId(),
            'allocated_amount' => $pa->getAllocatedAmount(),
        ];

        $model = $pa->getId() ? $this->update($pa->getId(), $data) : $this->create($data);

        /** @var PaymentAllocationModel $model */
        return $this->toDomainEntity($model);
    }

    private function mapToDomain(PaymentAllocationModel $m): PaymentAllocation
    {
        return new PaymentAllocation(
            paymentId: (int) $m->payment_id,
            invoiceType: (string) $m->invoice_type,
            invoiceId: (int) $m->invoice_id,
            allocatedAmount: (float) $m->allocated_amount,
            tenantId: $m->tenant_id !== null ? (int) $m->tenant_id : null,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
