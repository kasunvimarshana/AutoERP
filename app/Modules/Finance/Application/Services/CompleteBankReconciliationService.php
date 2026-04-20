<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\CompleteBankReconciliationServiceInterface;
use Modules\Finance\Domain\Entities\BankReconciliation;
use Modules\Finance\Domain\Exceptions\BankReconciliationNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankReconciliationRepositoryInterface;

class CompleteBankReconciliationService extends BaseService implements CompleteBankReconciliationServiceInterface
{
    public function __construct(private readonly BankReconciliationRepositoryInterface $bankReconciliationRepository)
    {
        parent::__construct($bankReconciliationRepository);
    }

    protected function handle(array $data): BankReconciliation
    {
        $id = (int) ($data['id'] ?? 0);
        $completedBy = (int) ($data['completed_by'] ?? 0);

        $bankReconciliation = $this->bankReconciliationRepository->find($id);
        if (! $bankReconciliation) {
            throw new BankReconciliationNotFoundException($id);
        }

        if ($bankReconciliation->getStatus() !== 'draft') {
            throw new DomainException('Only draft bank reconciliations can be completed.');
        }

        $bankReconciliation->complete($completedBy);

        return $this->bankReconciliationRepository->save($bankReconciliation);
    }
}
