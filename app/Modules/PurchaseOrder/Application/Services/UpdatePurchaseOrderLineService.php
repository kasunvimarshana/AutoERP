<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\PurchaseOrder\Application\Contracts\UpdatePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\DTOs\UpdatePurchaseOrderLineData;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrderLine;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderLineUpdated;
use Modules\PurchaseOrder\Domain\Exceptions\PurchaseOrderLineNotFoundException;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;

class UpdatePurchaseOrderLineService extends BaseService implements UpdatePurchaseOrderLineServiceInterface
{
    public function __construct(private readonly PurchaseOrderLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): PurchaseOrderLine
    {
        $dto  = UpdatePurchaseOrderLineData::fromArray($data);
        $line = $this->lineRepository->find($dto->id);

        if (! $line) {
            throw new PurchaseOrderLineNotFoundException($dto->id);
        }

        $line->updateDetails(
            $dto->quantityOrdered,
            $dto->unitPrice,
            $dto->discountPercent,
            $dto->taxPercent,
            $dto->lineTotal,
            $dto->expectedDate,
            $dto->notes,
            $dto->metadata,
        );

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new PurchaseOrderLineUpdated($saved->getId(), $saved->getPurchaseOrderId()));

        return $saved;
    }
}
