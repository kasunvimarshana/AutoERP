<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\UpdateSalesInvoiceServiceInterface;
use Modules\Sales\Application\DTOs\SalesInvoiceData;
use Modules\Sales\Application\DTOs\SalesInvoiceLineData;
use Modules\Sales\Domain\Entities\SalesInvoice;
use Modules\Sales\Domain\Entities\SalesInvoiceLine;
use Modules\Sales\Domain\Exceptions\SalesInvoiceNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;

class UpdateSalesInvoiceService extends BaseService implements UpdateSalesInvoiceServiceInterface
{
    public function __construct(private readonly SalesInvoiceRepositoryInterface $salesInvoiceRepository)
    {
        parent::__construct($salesInvoiceRepository);
    }

    protected function handle(array $data): SalesInvoice
    {
        $id = (int) ($data['id'] ?? 0);
        $invoice = $this->salesInvoiceRepository->find($id);

        if (! $invoice) {
            throw new SalesInvoiceNotFoundException($id);
        }

        $dto = SalesInvoiceData::fromArray($data);

        if ($invoice->getTenantId() !== $dto->tenantId) {
            throw new SalesInvoiceNotFoundException($id);
        }

        $invoiceDate = $dto->invoiceDate !== null
            ? new \DateTimeImmutable($dto->invoiceDate)
            : $invoice->getInvoiceDate();

        $dueDate = $dto->dueDate !== null
            ? new \DateTimeImmutable($dto->dueDate)
            : $invoice->getDueDate();

        $invoice->update(
            customerId: $dto->customerId,
            currencyId: $dto->currencyId,
            invoiceDate: $invoiceDate,
            dueDate: $dueDate,
            salesOrderId: $dto->salesOrderId,
            shipmentId: $dto->shipmentId,
            invoiceNumber: $dto->invoiceNumber,
            exchangeRate: $dto->exchangeRate,
            subtotal: $dto->subtotal,
            taxTotal: $dto->taxTotal,
            discountTotal: $dto->discountTotal,
            grandTotal: $dto->grandTotal,
            arAccountId: $dto->arAccountId,
            journalEntryId: $dto->journalEntryId,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        if ($dto->lines !== null) {
            $lines = array_map(
                static fn (array $lineData): SalesInvoiceLine => self::buildLine($dto->tenantId, $lineData),
                $dto->lines
            );
            $invoice->setLines($lines);
        }

        return $this->salesInvoiceRepository->save($invoice);
    }

    private static function buildLine(int $tenantId, array $lineData): SalesInvoiceLine
    {
        $lineData['tenant_id'] = $lineData['tenant_id'] ?? $tenantId;
        $lineDto = SalesInvoiceLineData::fromArray($lineData);

        return new SalesInvoiceLine(
            tenantId: $lineDto->tenantId,
            productId: $lineDto->productId,
            uomId: $lineDto->uomId,
            salesInvoiceId: $lineDto->salesInvoiceId,
            salesOrderLineId: $lineDto->salesOrderLineId,
            variantId: $lineDto->variantId,
            description: $lineDto->description,
            quantity: $lineDto->quantity,
            unitPrice: $lineDto->unitPrice,
            discountPct: $lineDto->discountPct,
            taxGroupId: $lineDto->taxGroupId,
            taxAmount: $lineDto->taxAmount,
            lineTotal: $lineDto->lineTotal,
            incomeAccountId: $lineDto->incomeAccountId,
            id: $lineDto->id,
        );
    }
}
