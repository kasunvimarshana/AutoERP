<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\FinanceCore;

use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\RecalculateLedgerBalancesServiceInterface;
use Modules\Finance\Application\Repositories\LedgerEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Throwable;

final class RecalculateLedgerBalancesService implements RecalculateLedgerBalancesServiceInterface
{
    public function __construct(
        private readonly LedgerEntryRepositoryInterface $ledgerEntries,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {
    }

    public function execute(array $payload): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
        if ($tenantId < 1) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, 'Tenant id is required.'));
        }

        try {
            $criteria = ['tenant_id' => $tenantId];
            if (array_key_exists('account_id', $payload) && $payload['account_id'] !== null) {
                $criteria['account_id'] = (int) $payload['account_id'];
            }
            if (
                array_key_exists('organization_unit_id', $payload)
                && $payload['organization_unit_id'] !== null
            ) {
                $criteria['organization_unit_id'] = (int) $payload['organization_unit_id'];
            }

            $entries = $this->ledgerEntries->list($criteria);
            if ($entries === []) {
                return Result::success([
                    'updated_count' => 0,
                    'account_count' => 0,
                ]);
            }

            usort($entries, [$this, 'compareLedgerEntries']);

            $updatedCount = $this->transactionManager->runInTransaction(function () use ($entries): int {
                $balances = [];
                $updated = 0;

                foreach ($entries as $entry) {
                    if (! $entry instanceof DataRecord) {
                        continue;
                    }

                    $accountId = (int) $entry->get('account_id', 0);
                    if (! array_key_exists($accountId, $balances)) {
                        $balances[$accountId] = 0.0;
                    }

                    $amount = (float) $entry->get('amount', 0);
                    $entryType = strtoupper(trim((string) $entry->get('entry_type', 'DEBIT')));
                    $signedAmount = $entryType === 'CREDIT' ? (-1 * $amount) : $amount;

                    $balances[$accountId] = round($balances[$accountId] + $signedAmount, 4);

                    $this->ledgerEntries->update((int) $entry->id(), [
                        'running_balance' => $balances[$accountId],
                        'row_version' => ((int) $entry->get('row_version', 1)) + 1,
                    ]);
                    $updated++;
                }

                return $updated;
            });

            $accountIds = [];
            foreach ($entries as $entry) {
                if ($entry instanceof DataRecord) {
                    $accountIds[] = (int) $entry->get('account_id', 0);
                }
            }

            return Result::success([
                'updated_count' => $updatedCount,
                'account_count' => count(array_unique($accountIds)),
            ]);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                FinanceErrorCode::INVALID_VALUE,
                ['tenant_id' => $tenantId],
            ));
        }
    }

    private function compareLedgerEntries(DataRecord $left, DataRecord $right): int
    {
        $byAccount = (int) $left->get('account_id', 0) <=> (int) $right->get('account_id', 0);
        if ($byAccount !== 0) {
            return $byAccount;
        }

        return (int) $left->id() <=> (int) $right->id();
    }
}
