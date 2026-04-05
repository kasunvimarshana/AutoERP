<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\CategorizeTransactionServiceInterface;
use Modules\Accounting\Domain\Entities\BankTransaction;
use Modules\Accounting\Domain\Events\TransactionCategorized;
use Modules\Accounting\Domain\Exceptions\BankTransactionNotFoundException;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\TransactionRuleRepositoryInterface;

class CategorizeTransactionService implements CategorizeTransactionServiceInterface
{
    public function __construct(
        private readonly BankTransactionRepositoryInterface $transactionRepo,
        private readonly TransactionRuleRepositoryInterface $ruleRepo,
    ) {}

    public function execute(int $transactionId, int $categoryId, int $accountId): BankTransaction
    {
        $transaction = $this->transactionRepo->findById($transactionId);
        if (!$transaction) throw new BankTransactionNotFoundException($transactionId);

        $transaction->categorize($categoryId, $accountId);
        $updated = $this->transactionRepo->update($transactionId, [
            'expense_category_id' => $categoryId,
            'account_id'          => $accountId,
            'status'              => 'categorized',
        ]);

        if (app()->bound('events')) {
            event(new TransactionCategorized($transaction->getTenantId(), $transactionId, $categoryId));
        }

        return $updated ?? $transaction;
    }

    public function autoApplyRules(int $tenantId): int
    {
        $rules        = $this->ruleRepo->findActiveByTenant($tenantId);
        $transactions = $this->transactionRepo->findPendingByTenant($tenantId);
        $count        = 0;

        foreach ($transactions as $transaction) {
            foreach ($rules as $rule) {
                if ($rule->matches($transaction)) {
                    $actions = $rule->getActions();
                    $categoryId = (int)($actions['expense_category_id'] ?? 0);
                    $accountId  = (int)($actions['account_id'] ?? 0);
                    if ($categoryId && $accountId) {
                        $this->transactionRepo->update($transaction->getId(), [
                            'expense_category_id' => $categoryId,
                            'account_id'          => $accountId,
                            'status'              => 'categorized',
                        ]);
                        $this->ruleRepo->incrementMatchCount($rule->getId());
                        $count++;
                        break;
                    }
                }
            }
        }

        return $count;
    }
}
