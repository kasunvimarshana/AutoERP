<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\UpdatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseInvoiceData;
use Modules\Purchase\Application\DTOs\PurchaseInvoiceLineData;
use Modules\Purchase\Application\Support\PurchasePricingCalculator;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\Entities\PurchaseInvoiceLine;
use Modules\Purchase\Domain\Exceptions\PurchaseInvoiceNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;

class UpdatePurchaseInvoiceService extends BaseService implements UpdatePurchaseInvoiceServiceInterface
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repo,
        private readonly PurchasePricingCalculator $pricingCalculator,
    ) {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseInvoice
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseInvoiceNotFoundException($id);
        }

        $merged = $this->mergePayloadWithExistingInvoice($entity, $data);
        $normalizedData = $this->pricingCalculator->normalizeInvoicePayload($merged);
        $dto = PurchaseInvoiceData::fromArray($normalizedData);

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

        if ($dto->lines !== null) {
            $lines = array_map(
                static fn (array $lineData): PurchaseInvoiceLine => self::buildLine($dto->tenantId, $lineData),
                $dto->lines
            );
            $entity->setLines($lines);
        }

        return $this->repo->save($entity);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergePayloadWithExistingInvoice(PurchaseInvoice $invoice, array $payload): array
    {
        $base = [
            'id' => $invoice->getId(),
            'tenant_id' => $invoice->getTenantId(),
            'supplier_id' => $invoice->getSupplierId(),
            'invoice_number' => $invoice->getInvoiceNumber(),
            'invoice_date' => $invoice->getInvoiceDate()->format('Y-m-d'),
            'due_date' => $invoice->getDueDate()->format('Y-m-d'),
            'currency_id' => $invoice->getCurrencyId(),
            'status' => $invoice->getStatus(),
            'exchange_rate' => $invoice->getExchangeRate(),
            'grn_header_id' => $invoice->getGrnHeaderId(),
            'purchase_order_id' => $invoice->getPurchaseOrderId(),
            'supplier_invoice_number' => $invoice->getSupplierInvoiceNumber(),
            'subtotal' => $invoice->getSubtotal(),
            'tax_total' => $invoice->getTaxTotal(),
            'discount_total' => $invoice->getDiscountTotal(),
            'grand_total' => $invoice->getGrandTotal(),
            'ap_account_id' => $invoice->getApAccountId(),
            'journal_entry_id' => $invoice->getJournalEntryId(),
        ];

        return array_merge($base, $payload);
    }

    private static function buildLine(int $tenantId, array $lineData): PurchaseInvoiceLine
    {
        $lineData['tenant_id'] = $lineData['tenant_id'] ?? $tenantId;
        $lineData['purchase_invoice_id'] = $lineData['purchase_invoice_id'] ?? 0;
        $lineData['line_total'] = $lineData['line_total'] ?? '0';
        $lineDto = PurchaseInvoiceLineData::fromArray($lineData);

        return new PurchaseInvoiceLine(
            tenantId: $lineDto->tenantId,
            purchaseInvoiceId: $lineDto->purchaseInvoiceId,
            productId: $lineDto->productId,
            uomId: $lineDto->uomId,
            quantity: $lineDto->quantity,
            unitPrice: $lineDto->unitPrice,
            lineTotal: $lineDto->lineTotal,
            discountPct: $lineDto->discountPct,
            taxAmount: $lineDto->taxAmount,
            grnLineId: $lineDto->grnLineId,
            variantId: $lineDto->variantId,
            description: $lineDto->description,
            taxGroupId: $lineDto->taxGroupId,
            accountId: $lineDto->accountId,
        );
    }
}
