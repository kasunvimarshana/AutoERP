<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Reporting\DTOs\ReportDefinition;

final class OperationalReportResponseBuilder
{
    /**
     * @param  object  $query  Query builder or Eloquent builder.
     * @param  Closure(object): array<string, mixed>  $mapper
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    public function paginate(
        object $query,
        Closure $mapper,
        ReportDefinition $definition,
        array $summary,
        int $page,
        int $perPage,
    ): array {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map($mapper)->values(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'report' => $definition->toArray(),
        ];
    }

    /**
     * @param  object  $query  Query builder or Eloquent builder.
     * @param  Closure(object): array<string, mixed>  $mapper
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(object $query, Closure $mapper): Collection
    {
        $limit = (int) config('reporting.export_row_limit', 5000);
        $count = (clone $query)->count();

        if ($count > $limit) {
            throw ValidationException::withMessages([
                'filters' => ["The report contains {$count} rows. Narrow the filters to {$limit} rows or fewer before exporting."],
            ]);
        }

        return $query->get()->map($mapper)->values();
    }
}
