<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Application\Contracts\FindJournalEntryServiceInterface;
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class FindJournalEntryService implements FindJournalEntryServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'fiscal_period_id', 'entry_type', 'status', 'entry_number', 'reference_type', 'reference_id'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'entry_date', 'posting_date', 'status', 'created_at', 'updated_at'];

    public function __construct(private readonly JournalEntryRepositoryInterface $journalEntryRepository) {}

    public function find(mixed $id): ?JournalEntry
    {
        return $this->journalEntryRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null): LengthAwarePaginator
    {
        $repository = $this->journalEntryRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }

            if (is_string($value) && $field === 'entry_number') {
                $repository->where('entry_number', 'like', '%'.$value.'%');

                continue;
            }

            $repository->where($field, $value);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function parseSort(?string $sort): array
    {
        if ($sort === null) {
            return [null, 'asc'];
        }

        $sort = trim($sort);

        if ($sort === '') {
            return [null, 'asc'];
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if (! in_array($field, self::ALLOWED_SORTS, true)) {
            return [null, 'asc'];
        }

        return [$field, $direction];
    }
}
