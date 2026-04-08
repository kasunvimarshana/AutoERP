<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Accounting\Application\Contracts\CategorizeTransactionServiceInterface;
use Modules\Accounting\Domain\Entities\BankTransaction;
use Modules\Accounting\Domain\Events\BankTransactionCategorized;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\TransactionRuleRepositoryInterface;
class CategorizeTransactionService implements CategorizeTransactionServiceInterface
{
    public function __construct(
        private readonly BankTransactionRepositoryInterface $transactionRepository,
        private readonly TransactionRuleRepositoryInterface $ruleRepository,
    ) {}
    public function categorize(string $tenantId, string $transactionId, string $accountId): BankTransaction
    {
        return DB::transaction(function () use ($tenantId, $transactionId, $accountId): BankTransaction {
            $tx = $this->transactionRepository->findById($tenantId, $transactionId);
            if ($tx === null) {
                throw new NotFoundException("Bank transaction [{$transactionId}] not found.");
            }
            $categorized = $tx->categorize($accountId);
            $this->transactionRepository->save($categorized);
            Event::dispatch(new BankTransactionCategorized($categorized));
            return $categorized;
        });
    }
    public function autoCategorize(string $tenantId, string $bankAccountId): int
    {
        $rules        = $this->ruleRepository->findActive($tenantId);
        $transactions = $this->transactionRepository->findByBankAccount($tenantId, $bankAccountId, ['status' => 'pending']);
        $count = 0;
        foreach ($transactions as $tx) {
            foreach ($rules as $rule) {
                if ($rule->matches($tx)) {
                    DB::transaction(function () use ($tenantId, $tx, $rule, &$count): void {
                        $categorized = $tx->categorize($rule->accountId);
                        $this->transactionRepository->save($categorized);
                        Event::dispatch(new BankTransactionCategorized($categorized));
                        $count++;
                    });
                    break;
                }
            }
        }
        return $count;
    }
}
