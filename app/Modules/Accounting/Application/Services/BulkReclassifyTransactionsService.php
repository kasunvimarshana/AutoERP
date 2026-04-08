<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Application\Contracts\BulkReclassifyTransactionsServiceInterface;
use Modules\Accounting\Domain\Events\BankTransactionCategorized;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
class BulkReclassifyTransactionsService implements BulkReclassifyTransactionsServiceInterface
{
    public function __construct(
        private readonly BankTransactionRepositoryInterface $transactionRepository,
    ) {}
    public function reclassify(string $tenantId, array $transactionIds, string $newAccountId): int
    {
        return DB::transaction(function () use ($tenantId, $transactionIds, $newAccountId): int {
            $count = 0;
            foreach ($transactionIds as $txId) {
                $tx = $this->transactionRepository->findById($tenantId, $txId);
                if ($tx === null) {
                    continue;
                }
                $reclassified = $tx->categorize($newAccountId);
                $this->transactionRepository->save($reclassified);
                Event::dispatch(new BankTransactionCategorized($reclassified));
                $count++;
            }
            return $count;
        });
    }
}
