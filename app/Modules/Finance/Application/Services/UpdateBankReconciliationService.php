<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdateBankReconciliationServiceInterface;
use Modules\Finance\Application\DTOs\BankReconciliationData;
use Modules\Finance\Domain\Entities\BankReconciliation;
use Modules\Finance\Domain\Exceptions\BankReconciliationNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankReconciliationRepositoryInterface;

class UpdateBankReconciliationService extends BaseService implements UpdateBankReconciliationServiceInterface
{
    public function __construct(private readonly BankReconciliationRepositoryInterface $bankReconciliationRepository)
    {
        parent::__construct($bankReconciliationRepository);
    }

    protected function handle(array $data): BankReconciliation
    {
        $dto = BankReconciliationData::fromArray($data);
        /** @var BankReconciliation|null $br */
        $br = $this->bankReconciliationRepository->find((int) $dto->id);
        if (! $br) {
            throw new BankReconciliationNotFoundException((int) $dto->id);
        }
        $br->updateBalances($dto->opening_balance, $dto->closing_balance);

        return $this->bankReconciliationRepository->save($br);
    }
}
