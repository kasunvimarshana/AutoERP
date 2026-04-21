<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Sales\Application\Contracts\CreateSalesReturnServiceInterface;
use Modules\Sales\Application\DTOs\SalesReturnData;
use Modules\Sales\Application\DTOs\SalesReturnLineData;
use Modules\Sales\Domain\Entities\SalesReturn;
use Modules\Sales\Domain\Entities\SalesReturnLine;
use Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;

class CreateSalesReturnService extends BaseService implements CreateSalesReturnServiceInterface
{
    public function __construct(private readonly SalesReturnRepositoryInterface $salesReturnRepository)
    {
        parent::__construct($salesReturnRepository);
    }

    protected function handle(array $data): SalesReturn
    {
        $dto = SalesReturnData::fromArray($data);

        $returnDate = $dto->returnDate !== null
            ? new \DateTimeImmutable($dto->returnDate)
            : new \DateTimeImmutable;

        $return = new SalesReturn(
            tenantId: $dto->tenantId,
            customerId: $dto->customerId,
            currencyId: $dto->currencyId,
            returnDate: $returnDate,
            originalSalesOrderId: $dto->originalSalesOrderId,
            originalInvoiceId: $dto->originalInvoiceId,
            returnNumber: $dto->returnNumber,
            status: $dto->status,
            returnReason: $dto->returnReason,
            exchangeRate: $dto->exchangeRate,
            subtotal: $dto->subtotal,
            taxTotal: $dto->taxTotal,
            restockingFeeTotal: $dto->restockingFeeTotal,
            grandTotal: $dto->grandTotal,
            creditMemoNumber: $dto->creditMemoNumber,
            journalEntryId: $dto->journalEntryId,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        if ($dto->lines !== null) {
            $lines = array_map(
                static fn (array $lineData): SalesReturnLine => self::buildLine($dto->tenantId, $lineData),
                $dto->lines
            );
            $return->setLines($lines);
        }

        return $this->salesReturnRepository->save($return);
    }

    private static function buildLine(int $tenantId, array $lineData): SalesReturnLine
    {
        $lineData['tenant_id'] = $lineData['tenant_id'] ?? $tenantId;
        $lineDto = SalesReturnLineData::fromArray($lineData);

        return new SalesReturnLine(
            tenantId: $lineDto->tenantId,
            productId: $lineDto->productId,
            toLocationId: $lineDto->toLocationId,
            uomId: $lineDto->uomId,
            salesReturnId: $lineDto->salesReturnId,
            originalSalesOrderLineId: $lineDto->originalSalesOrderLineId,
            variantId: $lineDto->variantId,
            batchId: $lineDto->batchId,
            serialId: $lineDto->serialId,
            returnQty: $lineDto->returnQty,
            unitPrice: $lineDto->unitPrice,
            lineTotal: $lineDto->lineTotal,
            condition: $lineDto->condition,
            disposition: $lineDto->disposition,
            restockingFee: $lineDto->restockingFee,
            qualityCheckNotes: $lineDto->qualityCheckNotes,
        );
    }
}
