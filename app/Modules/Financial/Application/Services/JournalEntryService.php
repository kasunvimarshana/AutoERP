<?php

declare(strict_types=1);

namespace Modules\Financial\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Financial\Application\Contracts\JournalEntryServiceInterface;
use Modules\Financial\Domain\Contracts\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Financial\Domain\Contracts\Repositories\JournalEntryRepositoryInterface;
use Modules\Financial\Domain\Events\JournalEntryPosted;
use Modules\Financial\Domain\Events\JournalEntryVoided;
use Modules\Financial\Domain\Exceptions\InvalidJournalEntryException;
use Modules\Financial\Domain\Exceptions\JournalEntryNotFoundException;

class JournalEntryService extends BaseService implements JournalEntryServiceInterface
{
    public function __construct(
        JournalEntryRepositoryInterface $repository,
        private readonly JournalEntryLineRepositoryInterface $lineRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — delegates to createJournalEntry.
     */
    protected function handle(array $data): mixed
    {
        return $this->createJournalEntry($data);
    }

    /**
     * Create a balanced journal entry with its lines.
     * Throws InvalidJournalEntryException when debits ≠ credits.
     */
    public function createJournalEntry(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];

            // Validate balance
            $totalDebit  = (float) array_sum(array_column($lines, 'debit'));
            $totalCredit = (float) array_sum(array_column($lines, 'credit'));

            if (abs($totalDebit - $totalCredit) > 0.0001) {
                throw new InvalidJournalEntryException($totalDebit, $totalCredit);
            }

            $headerData = array_merge(
                array_diff_key($data, ['lines' => null]),
                [
                    'total_debit'  => $totalDebit,
                    'total_credit' => $totalCredit,
                ],
            );

            $entry = $this->repository->create($headerData);

            foreach ($lines as $line) {
                $this->lineRepository->create(array_merge($line, [
                    'journal_entry_id' => $entry->id,
                    'tenant_id'        => $entry->tenant_id,
                ]));
            }

            return $entry;
        });
    }

    /**
     * Post a draft journal entry.
     */
    public function postJournalEntry(string $id): mixed
    {
        return DB::transaction(function () use ($id) {
            $entry = $this->repository->find($id);
            if (! $entry) {
                throw new JournalEntryNotFoundException($id);
            }

            $updated = $this->repository->update($id, [
                'status'    => 'posted',
                'posted_at' => now(),
            ]);

            $this->addEvent(new JournalEntryPosted((int) ($entry->tenant_id ?? 0), $id));
            $this->dispatchEvents();

            return $updated;
        });
    }

    /**
     * Void a posted journal entry.
     */
    public function voidJournalEntry(string $id, string $reason): mixed
    {
        return DB::transaction(function () use ($id, $reason) {
            $entry = $this->repository->find($id);
            if (! $entry) {
                throw new JournalEntryNotFoundException($id);
            }

            $updated = $this->repository->update($id, [
                'status'      => 'voided',
                'voided_at'   => now(),
                'void_reason' => $reason,
            ]);

            $this->addEvent(new JournalEntryVoided((int) ($entry->tenant_id ?? 0), $id));
            $this->dispatchEvents();

            return $updated;
        });
    }
}
