<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindBankReconciliationServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\BankReconciliationRepositoryInterface;

class FindBankReconciliationService extends BaseService implements FindBankReconciliationServiceInterface
{
    public function __construct(private readonly BankReconciliationRepositoryInterface $bankReconciliationRepository)
    {
        parent::__construct($bankReconciliationRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
