<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\UpdatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseInvoiceData;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\Exceptions\PurchaseInvoiceNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;

class UpdatePurchaseInvoiceService extends BaseService implements UpdatePurchaseInvoiceServiceInterface
{
    public function __construct(private readonly PurchaseInvoiceRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseInvoice
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseInvoiceNotFoundException($id);
        }

        $dto = PurchaseInvoiceData::fromArray($data);

        $entity->update(
            supplierId: $dto->supplierId,
            invoiceNumber: $dto->invoiceNumber,
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
