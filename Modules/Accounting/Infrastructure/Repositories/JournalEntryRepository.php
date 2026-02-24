<?php

namespace Modules\Accounting\Infrastructure\Repositories;

use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Infrastructure\Models\JournalEntryModel;
use Modules\Accounting\Infrastructure\Models\JournalLineModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class JournalEntryRepository extends BaseEloquentRepository implements JournalEntryRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new JournalEntryModel());
    }

    public function findById(string $id): ?object
    {
        return JournalEntryModel::with('lines')->find($id);
    }

    public function create(array $data): object
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        $entry = JournalEntryModel::create($data);

        foreach ($lines as $line) {
            $line['journal_entry_id'] = $entry->id;
            JournalLineModel::create($line);
        }

        return $entry->load('lines');
    }

    public function update(string $id, array $data): object
    {
        $entry = JournalEntryModel::findOrFail($id);
        $lines = $data['lines'] ?? null;
        unset($data['lines']);

        $entry->update($data);

        if ($lines !== null) {
            $entry->lines()->delete();
            foreach ($lines as $line) {
                $line['journal_entry_id'] = $entry->id;
                JournalLineModel::create($line);
            }
        }

        return $entry->load('lines');
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = JournalEntryModel::with('lines');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['entry_date'])) {
            $query->whereDate('entry_date', $filters['entry_date']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function nextNumber(string $tenantId): string
    {
        $count = JournalEntryModel::withTrashed()->where('tenant_id', $tenantId)->count();
        return 'JE-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}
