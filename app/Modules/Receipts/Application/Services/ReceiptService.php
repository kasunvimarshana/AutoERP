<?php

declare(strict_types=1);

namespace Modules\Receipts\Application\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Receipts\Application\Contracts\ReceiptServiceInterface;
use Modules\Receipts\Application\DTOs\CreateReceiptDTO;
use Modules\Receipts\Domain\Entities\Receipt;
use Modules\Receipts\Domain\Exceptions\ReceiptNotFoundException;
use Modules\Receipts\Domain\RepositoryInterfaces\ReceiptRepositoryInterface;
use Modules\Receipts\Domain\ValueObjects\ReceiptStatus;
use Ramsey\Uuid\Uuid;

class ReceiptService implements ReceiptServiceInterface
{
    public function __construct(private readonly ReceiptRepositoryInterface $repository)
    {
    }

    public function getById(string $id): Receipt
    {
        $receipt = $this->repository->findById($id);
        if ($receipt === null) {
            throw new ReceiptNotFoundException($id);
        }

        return $receipt;
    }

    public function listByTenant(string $tenantId, string $orgUnitId): array
    {
        return $this->repository->findByTenant($tenantId, $orgUnitId);
    }

    public function listByPayment(string $tenantId, string $paymentId): array
    {
        return $this->repository->findByPayment($tenantId, $paymentId);
    }

    public function create(CreateReceiptDTO $dto): Receipt
    {
        return DB::transaction(function () use ($dto): Receipt {
            $now = new DateTimeImmutable();

            $receipt = new Receipt(
                id: Uuid::uuid4()->toString(),
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                rowVersion: 1,
                receiptNumber: $dto->receiptNumber,
                paymentId: $dto->paymentId,
                invoiceId: $dto->invoiceId,
                receiptType: $dto->receiptType,
                status: ReceiptStatus::Issued,
                amount: $dto->amount,
                currency: $dto->currency,
                issuedAt: $now,
                notes: $dto->notes,
                metadata: $dto->metadata,
                isActive: true,
                createdAt: $now,
                updatedAt: $now,
            );

            return $this->repository->save($receipt);
        });
    }

    public function updateStatus(string $id, string $status): Receipt
    {
        return DB::transaction(function () use ($id, $status): Receipt {
            $this->getById($id);

            return $this->repository->updateStatus($id, $status);
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->getById($id);
            $this->repository->delete($id);
        });
    }
}
