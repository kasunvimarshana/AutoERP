<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\FindTransactionTaxServiceInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TransactionTaxRepositoryInterface;

class FindTransactionTaxService extends BaseService implements FindTransactionTaxServiceInterface
{
    public function __construct(private readonly TransactionTaxRepositoryInterface $transactionTaxRepository)
    {
        parent::__construct($transactionTaxRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }

    public function listByReference(int $tenantId, string $referenceType, int $referenceId): array
    {
        return $this->transactionTaxRepository->listByReference($tenantId, $referenceType, $referenceId);
    }
}
