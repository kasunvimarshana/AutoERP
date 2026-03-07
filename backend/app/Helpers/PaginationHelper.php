<?php
namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaginationHelper
{
    public static function paginate(mixed $source, ?int $perPage = null, int $page = 1): mixed
    {
        if ($perPage === null) {
            if ($source instanceof Builder) {
                return $source->get();
            }
            if ($source instanceof Collection) {
                return $source;
            }
            return collect($source);
        }

        if ($source instanceof Builder) {
            return $source->paginate($perPage, ['*'], 'page', $page);
        }

        if ($source instanceof Collection) {
            return self::paginateCollection($source, $perPage, $page);
        }

        return self::paginateCollection(collect($source), $perPage, $page);
    }

    private static function paginateCollection(Collection $collection, int $perPage, int $page): LengthAwarePaginator
    {
        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public static function fromRequest(\Illuminate\Http\Request $request): array
    {
        return [
            'per_page' => $request->has('per_page') ? (int) $request->input('per_page') : null,
            'page' => (int) $request->input('page', 1),
        ];
    }
}
