<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\UpdateInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\DTOs\UpdateInventoryCycleCountLineData;
use Modules\Inventory\Domain\Events\InventoryCycleCountLineRecorded;
use Modules\Inventory\Domain\Exceptions\InventoryCycleCountLineNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountLineRepositoryInterface;

class UpdateInventoryCycleCountLineService extends BaseService implements UpdateInventoryCycleCountLineServiceInterface
{
    public function __construct(private readonly InventoryCycleCountLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto  = UpdateInventoryCycleCountLineData::fromArray($data);
        $line = $this->lineRepository->find($dto->id);

        if (! $line) {
            throw new InventoryCycleCountLineNotFoundException($dto->id);
        }

        if (isset($data['counted_qty'])) {
            $line->recordCount((float) $data['counted_qty'], $data['counted_by'] ?? null);
        }

        $saved = $this->lineRepository->save($line);

        return $saved;
    }
}
