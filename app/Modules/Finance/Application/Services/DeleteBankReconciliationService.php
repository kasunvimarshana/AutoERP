<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteBankReconciliationServiceInterface;
use Modules\Finance\Domain\Exceptions\BankReconciliationNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankReconciliationRepositoryInterface;

class DeleteBankReconciliationService extends BaseService implements DeleteBankReconciliationServiceInterface
{
    public function __construct(private readonly BankReconciliationRepositoryInterface $bankReconciliationRepository)
    {
        parent::__construct($bankReconciliationRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->bankReconciliationRepository->find($id)) {
            throw new BankReconciliationNotFoundException($id);
        }

        return $this->bankReconciliationRepository->delete($id);
    }
}
