<?php

declare(strict_types=1);

namespace Modules\Invoicing\Application\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Invoicing\Application\Contracts\InvoiceServiceInterface;
use Modules\Invoicing\Application\DTOs\CreateInvoiceDTO;
use Modules\Invoicing\Application\DTOs\RecordInvoicePaymentDTO;
use Modules\Invoicing\Domain\Entities\Invoice;
use Modules\Invoicing\Domain\Exceptions\InvoiceNotFoundException;
use Modules\Invoicing\Domain\RepositoryInterfaces\InvoiceRepositoryInterface;
use Modules\Invoicing\Domain\ValueObjects\InvoiceStatus;
use Ramsey\Uuid\Uuid;

class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(private readonly InvoiceRepositoryInterface $repository)
    {
    }

    public function getById(string $id): Invoice
    {
        $invoice = $this->repository->findById($id);
        if ($invoice === null) {
            throw new InvoiceNotFoundException($id);
        }

        return $invoice;
    }

    public function listByTenant(string $tenantId, string $orgUnitId): array
    {
        return $this->repository->findByTenant($tenantId, $orgUnitId);
    }

    public function listByEntity(string $tenantId, string $entityType, string $entityId): array
    {
        return $this->repository->findByEntity($tenantId, $entityType, $entityId);
    }

    public function create(CreateInvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto): Invoice {
            $now = new DateTimeImmutable();

            $invoice = new Invoice(
                id: Uuid::uuid4()->toString(),
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                rowVersion: 1,
                invoiceNumber: $dto->invoiceNumber,
                invoiceType: $dto->invoiceType,
                entityType: $dto->entityType,
                entityId: $dto->entityId,
                status: InvoiceStatus::Draft,
                issueDate: $dto->issueDate,
                dueDate: $dto->dueDate,
                subtotalAmount: $dto->subtotalAmount,
                taxAmount: $dto->taxAmount,
                totalAmount: $dto->totalAmount,
                paidAmount: '0.000000',
                balanceAmount: $dto->totalAmount,
                currency: $dto->currency,
                notes: $dto->notes,
                metadata: $dto->metadata,
                isActive: true,
                createdAt: $now,
                updatedAt: $now,
            );

            return $this->repository->save($invoice);
        });
    }

    public function updateStatus(string $id, string $status): Invoice
    {
        return DB::transaction(function () use ($id, $status): Invoice {
            $this->getById($id);

            return $this->repository->updateStatus($id, $status);
        });
    }

    public function recordPayment(RecordInvoicePaymentDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto): Invoice {
            $this->getById($dto->id);

            return $this->repository->recordPayment($dto->id, $dto->amount);
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
