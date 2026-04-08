<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\FindStockReturnLineServiceInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;

class FindStockReturnLineService extends BaseService implements FindStockReturnLineServiceInterface
{
    public function __construct(private readonly StockReturnLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    public function findByReturn(int $tenantId, int $returnId): Collection
    {
        return $this->lineRepository->findByReturn($tenantId, $returnId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
