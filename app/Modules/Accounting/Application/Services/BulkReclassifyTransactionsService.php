<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\BulkReclassifyTransactionsServiceInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;

class BulkReclassifyTransactionsService implements BulkReclassifyTransactionsServiceInterface
{
    public function __construct(
        private readonly BankTransactionRepositoryInterface $transactionRepo,
    ) {}

    public function execute(array $transactionIds, int $categoryId, int $accountId): int
    {
        if (empty($transactionIds)) return 0;
        return $this->transactionRepo->updateBatch($transactionIds, [
            'expense_category_id' => $categoryId,
            'account_id'          => $accountId,
            'status'              => 'categorized',
        ]);
    }
}
