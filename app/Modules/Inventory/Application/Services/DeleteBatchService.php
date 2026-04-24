<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\DeleteBatchServiceInterface;
use Modules\Inventory\Domain\Exceptions\BatchNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\BatchRepositoryInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;

class DeleteBatchService implements DeleteBatchServiceInterface
{
    public function __construct(
        private readonly BatchRepositoryInterface $batchRepository,
        private readonly RefreshProductSearchProjectionServiceInterface $refreshProjectionService,
    ) {}

    public function execute(array $data): bool
    {
        $id = (int) $data['id'];

        $batch = $this->batchRepository->find($id);

        if ($batch === null) {
            throw new BatchNotFoundException($id);
        }

        $tenantId  = $batch->getTenantId();
        $productId = $batch->getProductId();

        $deleted = DB::transaction(fn (): bool => $this->batchRepository->delete($id));

        $this->refreshProjectionService->execute($tenantId, $productId);

        return $deleted;
    }
}
