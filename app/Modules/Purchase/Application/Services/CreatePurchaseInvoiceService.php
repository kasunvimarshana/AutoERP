<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\CreatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseInvoiceData;
use Modules\Purchase\Application\DTOs\PurchaseInvoiceLineData;
use Modules\Purchase\Application\Support\PurchasePricingCalculator;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\Entities\PurchaseInvoiceLine;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;

class CreatePurchaseInvoiceService extends BaseService implements CreatePurchaseInvoiceServiceInterface
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repo,
        private readonly PurchasePricingCalculator $pricingCalculator,
    ) {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseInvoice
    {
        $normalizedData = $this->pricingCalculator->normalizeInvoicePayload($data);
        $dto = PurchaseInvoiceData::fromArray($normalizedData);

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

        if ($dto->lines !== null) {
            $lines = array_map(
                static fn (array $lineData): PurchaseInvoiceLine => self::buildLine($dto->tenantId, $lineData),
                $dto->lines
            );
            $entity->setLines($lines);
        }

        return $this->repo->save($entity);
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
