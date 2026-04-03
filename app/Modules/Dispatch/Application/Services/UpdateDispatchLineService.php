<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Dispatch\Application\Contracts\UpdateDispatchLineServiceInterface;
use Modules\Dispatch\Application\DTOs\UpdateDispatchLineData;
use Modules\Dispatch\Domain\Entities\DispatchLine;
use Modules\Dispatch\Domain\Events\DispatchLineUpdated;
use Modules\Dispatch\Domain\Exceptions\DispatchLineNotFoundException;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;

class UpdateDispatchLineService extends BaseService implements UpdateDispatchLineServiceInterface
{
    public function __construct(private readonly DispatchLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): DispatchLine
    {
        $dto  = UpdateDispatchLineData::fromArray($data);
        $line = $this->lineRepository->find($dto->id);

        if (! $line) {
            throw new DispatchLineNotFoundException($dto->id);
        }

        $line->updateDetails(
            $dto->description,
            $dto->quantity,
            $dto->warehouseLocationId,
            $dto->batchNumber,
            $dto->serialNumber,
            $dto->weight,
            $dto->notes,
            $dto->metadata,
        );

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new DispatchLineUpdated($saved->getId(), $saved->getDispatchId()));

        return $saved;
    }
}
