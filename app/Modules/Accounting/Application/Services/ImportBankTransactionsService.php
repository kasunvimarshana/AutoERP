<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\ImportBankTransactionsServiceInterface;
use Modules\Accounting\Domain\Events\BankTransactionImported;
use Modules\Accounting\Domain\Exceptions\BankAccountNotFoundException;
use Modules\Accounting\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;

class ImportBankTransactionsService implements ImportBankTransactionsServiceInterface
{
    public function __construct(
        private readonly BankAccountRepositoryInterface $bankAccountRepo,
        private readonly BankTransactionRepositoryInterface $transactionRepo,
    ) {}

    public function execute(int $bankAccountId, array $transactions): int
    {
        $bankAccount = $this->bankAccountRepo->findById($bankAccountId);
        if (!$bankAccount) throw new BankAccountNotFoundException($bankAccountId);

        $records = array_map(fn(array $t) => array_merge($t, [
            'bank_account_id' => $bankAccountId,
            'tenant_id'       => $bankAccount->getTenantId(),
            'status'          => 'pending',
            'source'          => $t['source'] ?? 'import',
            'metadata'        => $t['metadata'] ?? [],
        ]), $transactions);

        $count = $this->transactionRepo->createBatch($records);

        if (app()->bound('events')) {
            event(new BankTransactionImported($bankAccount->getTenantId(), $bankAccountId, $count));
        }

        return $count;
    }
}
