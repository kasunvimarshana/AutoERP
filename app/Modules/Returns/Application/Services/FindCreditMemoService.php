<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\FindCreditMemoServiceInterface;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class FindCreditMemoService extends BaseService implements FindCreditMemoServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    public function findByStockReturn(int $tenantId, int $stockReturnId): Collection
    {
        return $this->creditMemoRepository->findByStockReturn($tenantId, $stockReturnId);
    }

    public function findByParty(int $tenantId, int $partyId, string $partyType): Collection
    {
        return $this->creditMemoRepository->findByParty($tenantId, $partyId, $partyType);
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->creditMemoRepository->findByStatus($tenantId, $status);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
