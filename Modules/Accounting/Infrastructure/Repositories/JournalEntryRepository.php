<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Repositories;

use DateTimeImmutable;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Entities\JournalEntry as JournalEntryEntity;
use Modules\Accounting\Domain\Entities\JournalLine as JournalLineEntity;
use Modules\Accounting\Infrastructure\Models\JournalEntry as JournalEntryModel;

class JournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?JournalEntryEntity
    {
        $m = JournalEntryModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with('lines')
            ->first();

        return $m ? $this->toDomain($m) : null;
    }

    public function findPaginated(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = JournalEntryModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with('lines')
            ->orderByDesc('entry_date')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data'         => collect($paginator->items())
                ->map(fn (JournalEntryModel $m): JournalEntryEntity => $this->toDomain($m))
                ->all(),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];
    }

    public function save(JournalEntryEntity $entry): JournalEntryEntity
    {
        $m = JournalEntryModel::withoutGlobalScope('tenant')->updateOrCreate(
            ['id' => $entry->getId()],
            [
                'tenant_id'      => $entry->getTenantId(),
                'entry_number'   => $entry->getEntryNumber(),
                'entry_date'     => $entry->getEntryDate()->format('Y-m-d'),
                'description'    => $entry->getDescription(),
                'reference_type' => $entry->getReferenceType(),
                'reference_id'   => $entry->getReferenceId(),
                'is_posted'      => $entry->isPosted(),
                'is_reversed'    => $entry->isReversed(),
            ]
        );

        return $this->toDomain($m->load('lines'));
    }

    private function toDomain(JournalEntryModel $m): JournalEntryEntity
    {
        $lines = $m->lines->map(fn ($l): JournalLineEntity => new JournalLineEntity(
            accountId: (int) $l->account_id,
            description: $l->description,
            debitAmount: bcadd((string) $l->debit_amount, '0', 4),
            creditAmount: bcadd((string) $l->credit_amount, '0', 4),
        ))->all();

        return new JournalEntryEntity(
            id: (int) $m->id,
            tenantId: (int) $m->tenant_id,
            entryNumber: (string) $m->entry_number,
            entryDate: new DateTimeImmutable((string) $m->entry_date),
            description: (string) $m->description,
            referenceType: $m->reference_type,
            referenceId: $m->reference_id ? (int) $m->reference_id : null,
            isPosted: (bool) $m->is_posted,
            isReversed: (bool) $m->is_reversed,
            lines: $lines,
        );
    }
}
