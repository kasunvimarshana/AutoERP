<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Returns\Application\Contracts\CreateStockReturnServiceInterface;
use Modules\Returns\Application\DTOs\StockReturnData;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnCreated;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;

class CreateStockReturnService extends BaseService implements CreateStockReturnServiceInterface
{
    public function __construct(private readonly StockReturnRepositoryInterface $returnRepository)
    {
        parent::__construct($returnRepository);
    }

    protected function handle(array $data): StockReturn
    {
        $dto = StockReturnData::fromArray($data);

        $return = new StockReturn(
            tenantId:              $dto->tenantId,
            referenceNumber:       $dto->referenceNumber,
            returnType:            $dto->returnType,
            partyId:               $dto->partyId,
            partyType:             $dto->partyType,
            originalReferenceId:   $dto->originalReferenceId,
            originalReferenceType: $dto->originalReferenceType,
            returnReason:          $dto->returnReason,
            totalAmount:           $dto->totalAmount,
            currency:              $dto->currency,
            restock:               $dto->restock,
            restockLocationId:     $dto->restockLocationId,
            restockingFee:         $dto->restockingFee,
            notes:                 $dto->notes,
            metadata:              $dto->metadata ? new Metadata($dto->metadata) : null,
            status:                $dto->status,
        );

        $saved = $this->returnRepository->save($return);
        $this->addEvent(new StockReturnCreated($saved));

        return $saved;
    }
}
