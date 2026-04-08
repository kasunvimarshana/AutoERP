<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\FindStockReturnServiceInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;

class FindStockReturnService extends BaseService implements FindStockReturnServiceInterface
{
    public function __construct(private readonly StockReturnRepositoryInterface $returnRepository)
    {
        parent::__construct($returnRepository);
    }

    public function findByParty(int $tenantId, int $partyId, string $partyType): Collection
    {
        return $this->returnRepository->findByParty($tenantId, $partyId, $partyType);
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->returnRepository->findByStatus($tenantId, $status);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
