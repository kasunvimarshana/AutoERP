<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\StockMovement\Application\Contracts\TransferStockServiceInterface;
use Modules\StockMovement\Application\DTOs\TransferStockData;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\Events\StockMovementCreated;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

/**
 * TransferStockService
 *
 * Orchestrates a stock transfer between two locations by creating two
 * confirmed StockMovement records:
 *  1. An ISSUE movement from the source location (fromLocationId → null)
 *  2. A RECEIPT movement to the destination location (null → toLocationId)
 *
 * Both movements reference the same transfer reference number for full
 * audit traceability across inbound and outbound legs.
 *
 * Returns the RECEIPT movement as the primary result; callers may query
 * the paired ISSUE movement via the shared reference_number.
 */
class TransferStockService extends BaseService implements TransferStockServiceInterface
{
    public function __construct(private readonly StockMovementRepositoryInterface $movementRepository)
    {
        parent::__construct($movementRepository);
    }

    protected function handle(array $data): StockMovement
    {
        $dto      = TransferStockData::fromArray($data);
        $metadata = $dto->metadata ? new Metadata($dto->metadata) : null;
        $now      = new \DateTimeImmutable;

        // ── Issue movement (stock leaves source location) ────────────────
        $issue = new StockMovement(
            tenantId:        $dto->tenantId,
            referenceNumber: $dto->referenceNumber . '-OUT',
            movementType:    'issue',
            productId:       $dto->productId,
            quantity:        $dto->quantity,
            variationId:     $dto->variationId,
            fromLocationId:  $dto->fromLocationId,
            toLocationId:    null,
            batchId:         $dto->batchId,
            serialNumberId:  $dto->serialNumberId,
            uomId:           $dto->uomId,
            unitCost:        $dto->unitCost,
            currency:        $dto->currency,
            referenceType:   'stock_transfer',
            notes:           $dto->notes,
            performedBy:     $dto->performedBy,
            movementDate:    $now,
            metadata:        $metadata,
            status:          'confirmed',
        );
        $issue->confirm();
        $savedIssue = $this->movementRepository->save($issue);
        $this->addEvent(new StockMovementCreated($savedIssue));

        // ── Receipt movement (stock arrives at destination location) ──────
        $receipt = new StockMovement(
            tenantId:        $dto->tenantId,
            referenceNumber: $dto->referenceNumber . '-IN',
            movementType:    'receipt',
            productId:       $dto->productId,
            quantity:        $dto->quantity,
            variationId:     $dto->variationId,
            fromLocationId:  null,
            toLocationId:    $dto->toLocationId,
            batchId:         $dto->batchId,
            serialNumberId:  $dto->serialNumberId,
            uomId:           $dto->uomId,
            unitCost:        $dto->unitCost,
            currency:        $dto->currency,
            referenceType:   'stock_transfer',
            notes:           $dto->notes,
            performedBy:     $dto->performedBy,
            movementDate:    $now,
            metadata:        $metadata,
            status:          'confirmed',
        );
        $receipt->confirm();
        $savedReceipt = $this->movementRepository->save($receipt);
        $this->addEvent(new StockMovementCreated($savedReceipt));

        return $savedReceipt;
    }
}
