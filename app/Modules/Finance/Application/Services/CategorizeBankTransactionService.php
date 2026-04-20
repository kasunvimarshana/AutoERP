<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CategorizeBankTransactionServiceInterface;
use Modules\Finance\Domain\Entities\BankTransaction;
use Modules\Finance\Domain\Exceptions\BankTransactionNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;

class CategorizeBankTransactionService extends BaseService implements CategorizeBankTransactionServiceInterface
{
    public function __construct(private readonly BankTransactionRepositoryInterface $bankTransactionRepository)
    {
        parent::__construct($bankTransactionRepository);
    }

    protected function handle(array $data): BankTransaction
    {
        $id = (int) ($data['id'] ?? 0);
        $categoryRuleId = (int) ($data['category_rule_id'] ?? 0);

        $bankTransaction = $this->bankTransactionRepository->find($id);
        if (! $bankTransaction) {
            throw new BankTransactionNotFoundException($id);
        }

        $bankTransaction->categorize($categoryRuleId);

        return $this->bankTransactionRepository->save($bankTransaction);
    }
}
