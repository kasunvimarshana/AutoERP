<?php

declare(strict_types=1);

namespace Modules\Payments\Application\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Payments\Application\Contracts\PaymentServiceInterface;
use Modules\Payments\Application\DTOs\CreatePaymentDTO;
use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\Exceptions\PaymentNotFoundException;
use Modules\Payments\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;
use Ramsey\Uuid\Uuid;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $repository)
    {
    }

    public function getById(string $tenantId, string $id): Payment
    {
        $payment = $this->repository->findById($tenantId, $id);
        if ($payment === null) {
            throw new PaymentNotFoundException($id);
        }

        return $payment;
    }

    public function listByTenant(string $tenantId, string $orgUnitId): array
    {
        return $this->repository->findByTenant($tenantId, $orgUnitId);
    }

    public function listByInvoice(string $tenantId, string $invoiceId): array
    {
        return $this->repository->findByInvoice($tenantId, $invoiceId);
    }

    public function create(CreatePaymentDTO $dto): Payment
    {
        return DB::transaction(function () use ($dto): Payment {
            $now = new DateTimeImmutable();

            $payment = new Payment(
                id: Uuid::uuid4()->toString(),
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                rowVersion: 1,
                paymentNumber: $dto->paymentNumber,
                invoiceId: $dto->invoiceId,
                paymentMethod: $dto->paymentMethod,
                status: PaymentStatus::Pending,
                amount: $dto->amount,
                currency: $dto->currency,
                paidAt: null,
                referenceNumber: $dto->referenceNumber,
                notes: $dto->notes,
                metadata: $dto->metadata,
                isActive: true,
                createdAt: $now,
                updatedAt: $now,
            );

            return $this->repository->save($payment);
        });
    }

    public function updateStatus(string $tenantId, string $id, string $status): Payment
    {
        return DB::transaction(function () use ($tenantId, $id, $status): Payment {
            $this->getById($tenantId, $id);

            return $this->repository->updateStatus($tenantId, $id, $status);
        });
    }

    public function delete(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getById($tenantId, $id);
            $this->repository->delete($tenantId, $id);
        });
    }
}
