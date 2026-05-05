<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\CreatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseInvoiceData;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;

class CreatePurchaseInvoiceService extends BaseService implements CreatePurchaseInvoiceServiceInterface
{
    public function __construct(private readonly PurchaseInvoiceRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseInvoice
    {
        $dto = PurchaseInvoiceData::fromArray($data);

        $entity = new PurchaseInvoice(
            tenantId: $dto->tenantId,
            supplierId: $dto->supplierId,
            invoiceNumber: $dto->invoiceNumber,
            status: $dto->status,
            invoiceDate: new \DateTimeImmutable($dto->invoiceDate),
            dueDate: new \DateTimeImmutable($dto->dueDate),
            currencyId: $dto->currencyId,
            exchangeRate: $dto->exchangeRate,
            grnHeaderId: $dto->grnHeaderId,
            purchaseOrderId: $dto->purchaseOrderId,
            supplierInvoiceNumber: $dto->supplierInvoiceNumber,
            subtotal: $dto->subtotal,
            taxTotal: $dto->taxTotal,
            discountTotal: $dto->discountTotal,
            grandTotal: $dto->grandTotal,
            apAccountId: $dto->apAccountId,
            journalEntryId: $dto->journalEntryId,
        );

        return $this->repo->save($entity);
    }
}
