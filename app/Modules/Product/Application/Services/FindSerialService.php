<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\FindSerialServiceInterface;
use Modules\Product\Domain\Entities\Serial;
use Modules\Product\Domain\RepositoryInterfaces\SerialRepositoryInterface;

class FindSerialService implements FindSerialServiceInterface
{
    private const ALLOWED_FILTERS = ['tenant_id', 'product_id', 'variant_id', 'batch_id', 'status', 'serial_number'];

    private const ALLOWED_SORTS = ['id', 'serial_number', 'status', 'sold_at', 'created_at', 'updated_at'];

    public function __construct(private readonly SerialRepositoryInterface $serialRepository) {}

    public function find(mixed $id): ?Serial
    {
        return $this->serialRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->serialRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
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

    /** @return array{0: ?string, 1: string} */
    private function parseSort(?string $sort): array
    {
        if ($sort === null || trim($sort) === '') {
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
