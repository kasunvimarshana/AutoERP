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
            tenantId: $dto->tenant_id,
            supplierId: $dto->supplier_id,
            invoiceNumber: $dto->invoice_number,
            status: $dto->status,
            invoiceDate: new \DateTimeImmutable($dto->invoice_date),
            dueDate: new \DateTimeImmutable($dto->due_date),
            currencyId: $dto->currency_id,
            exchangeRate: $dto->exchange_rate,
            grnHeaderId: $dto->grn_header_id,
            purchaseOrderId: $dto->purchase_order_id,
            supplierInvoiceNumber: $dto->supplier_invoice_number,
            subtotal: $dto->subtotal,
            taxTotal: $dto->tax_total,
            discountTotal: $dto->discount_total,
            grandTotal: $dto->grand_total,
            apAccountId: $dto->ap_account_id,
            journalEntryId: $dto->journal_entry_id,
        );

        return $this->repo->save($entity);
    }
}
