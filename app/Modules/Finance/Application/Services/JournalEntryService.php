<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Finance\Application\Contracts\JournalEntryServiceInterface;
use Modules\Finance\Application\DTOs\JournalEntryData;
use Modules\Finance\Domain\Events\AccountBalanceUpdated;
use Modules\Finance\Domain\Events\JournalEntryPosted;
use Modules\Finance\Domain\Exceptions\JournalEntryAlreadyPostedException;
use Modules\Finance\Domain\Exceptions\UnbalancedJournalEntryException;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;

final class JournalEntryService implements JournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $repository,
        private readonly AccountRepositoryInterface      $accountRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function create(JournalEntryData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $referenceNumber = $this->repository->nextReferenceNumber($tenantId);

            $entry = $this->repository->create([
                'uuid'             => (string) Str::uuid(),
                'tenant_id'        => $tenantId,
                'reference_number' => $referenceNumber,
                'entry_date'       => $dto->entry_date,
                'description'      => $dto->description,
                'status'           => 'draft',
                'currency'         => $dto->currency,
                'source_type'      => $dto->source_type,
                'source_id'        => $dto->source_id,
                'metadata'         => $dto->metadata,
                'total_debit'      => 0.0,
                'total_credit'     => 0.0,
            ]);

            $totalDebit  = 0.0;
            $totalCredit = 0.0;

            foreach ($dto->getLineData() as $index => $lineDto) {
                JournalEntryLineModel::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $lineDto->account_id,
                    'description'      => $lineDto->description,
                    'debit_amount'     => $lineDto->debit_amount,
                    'credit_amount'    => $lineDto->credit_amount,
                    'currency'         => $lineDto->currency,
                    'exchange_rate'    => $lineDto->exchange_rate,
                    'sort_order'       => $lineDto->sort_order ?: $index,
                    'metadata'         => $lineDto->metadata,
                ]);

                $totalDebit  += $lineDto->debit_amount;
                $totalCredit += $lineDto->credit_amount;
            }

            $entry->update([
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            return $entry->fresh(['lines']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, JournalEntryData $dto): mixed
    {
        $entry = $this->repository->findWithLines($id);
        if (! $entry) {
            throw new NotFoundException('JournalEntry', $id);
        }

        if ($entry->status !== 'draft') {
            throw new JournalEntryAlreadyPostedException($id);
        }

        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($id, $dto, $entry) {
            $entry->lines()->delete();

            $totalDebit  = 0.0;
            $totalCredit = 0.0;

            foreach ($dto->getLineData() as $index => $lineDto) {
                JournalEntryLineModel::create([
                    'journal_entry_id' => $id,
                    'account_id'       => $lineDto->account_id,
                    'description'      => $lineDto->description,
                    'debit_amount'     => $lineDto->debit_amount,
                    'credit_amount'    => $lineDto->credit_amount,
                    'currency'         => $lineDto->currency,
                    'exchange_rate'    => $lineDto->exchange_rate,
                    'sort_order'       => $lineDto->sort_order ?: $index,
                    'metadata'         => $lineDto->metadata,
                ]);

                $totalDebit  += $lineDto->debit_amount;
                $totalCredit += $lineDto->credit_amount;
            }

            $this->repository->update($id, [
                'entry_date'   => $dto->entry_date,
                'description'  => $dto->description,
                'currency'     => $dto->currency,
                'source_type'  => $dto->source_type,
                'source_id'    => $dto->source_id,
                'metadata'     => $dto->metadata,
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            return $this->repository->findWithLines($id);
        });
    }

    /**
     * {@inheritdoc}
     *
     * ACID: validate balance → update account balances → mark posted → emit event.
     */
    public function post(int $id): mixed
    {
        return DB::transaction(function () use ($id) {
            $entry = $this->repository->findWithLines($id);
            if (! $entry) {
                throw new NotFoundException('JournalEntry', $id);
            }

            if ($entry->status === 'posted') {
                throw new JournalEntryAlreadyPostedException($id);
            }

            if ($entry->status === 'voided') {
                throw new JournalEntryAlreadyPostedException($id);
            }

            $totalDebit  = (float) $entry->total_debit;
            $totalCredit = (float) $entry->total_credit;

            if (abs($totalDebit - $totalCredit) >= 0.000001) {
                throw new UnbalancedJournalEntryException($totalDebit, $totalCredit);
            }

            foreach ($entry->lines as $line) {
                $account = $this->accountRepository->find($line->account_id);
                if (! $account) {
                    throw new \Modules\Finance\Domain\Exceptions\AccountNotFoundException($line->account_id);
                }

                $previousBalance = (float) $account->current_balance;

                if ($line->debit_amount > 0) {
                    $this->accountRepository->updateBalance(
                        $line->account_id,
                        (float) $line->debit_amount,
                        'debit'
                    );
                }

                if ($line->credit_amount > 0) {
                    $this->accountRepository->updateBalance(
                        $line->account_id,
                        (float) $line->credit_amount,
                        'credit'
                    );
                }

                $account->refresh();
                $newBalance = (float) $account->current_balance;

                Event::dispatch(new AccountBalanceUpdated(
                    tenantId:        $entry->tenant_id,
                    accountId:       $account->id,
                    accountCode:     $account->code,
                    previousBalance: $previousBalance,
                    newBalance:      $newBalance,
                    side:            $line->debit_amount > 0 ? 'debit' : 'credit',
                    amount:          $line->debit_amount > 0 ? (float) $line->debit_amount : (float) $line->credit_amount,
                ));
            }

            $userId = Auth::id() ?? 0;

            $this->repository->update($id, [
                'status'    => 'posted',
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);

            $entry = $this->repository->findWithLines($id);

            Event::dispatch(new JournalEntryPosted(
                tenantId:        $entry->tenant_id,
                journalEntryId:  $entry->id,
                referenceNumber: $entry->reference_number,
                totalDebit:      (float) $entry->total_debit,
                totalCredit:     (float) $entry->total_credit,
                postedBy:        $userId,
            ));

            return $entry;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function void(int $id, string $reason): mixed
    {
        return DB::transaction(function () use ($id, $reason) {
            $entry = $this->repository->find($id);
            if (! $entry) {
                throw new NotFoundException('JournalEntry', $id);
            }

            if ($entry->status === 'voided') {
                throw new JournalEntryAlreadyPostedException($id);
            }

            $userId = Auth::id() ?? 0;

            $this->repository->update($id, [
                'status'      => 'voided',
                'voided_at'   => now(),
                'voided_by'   => $userId,
                'void_reason' => $reason,
            ]);

            return $this->repository->find($id);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $entry = $this->repository->find($id);
        if (! $entry) {
            throw new NotFoundException('JournalEntry', $id);
        }

        if ($entry->status !== 'draft') {
            throw new JournalEntryAlreadyPostedException($id);
        }

        return $this->repository->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function find(mixed $id): mixed
    {
        $entry = $this->repository->findWithLines($id);
        if (! $entry) {
            throw new NotFoundException('JournalEntry', $id);
        }

        return $entry;
    }

    /**
     * {@inheritdoc}
     */
    public function list(array $filters = [], ?int $perPage = null): mixed
    {
        $perPage = $perPage ?? config('core.pagination.per_page', 15);

        $repo = clone $this->repository;

        foreach ($filters as $column => $value) {
            $repo->where($column, $value);
        }

        return $repo->paginate($perPage);
    }
}
