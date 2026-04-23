<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\FindProductAttachmentServiceInterface;
use Modules\Product\Domain\Entities\ProductAttachment;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttachmentRepositoryInterface;

class FindProductAttachmentService implements FindProductAttachmentServiceInterface
{
    private const ALLOWED_FILTERS = ['tenant_id', 'product_id', 'variant_id', 'type', 'is_primary'];

    private const ALLOWED_SORTS = ['id', 'sort_order', 'type', 'created_at', 'updated_at'];

    public function __construct(private readonly ProductAttachmentRepositoryInterface $productAttachmentRepository) {}

    public function find(mixed $id): ?ProductAttachment
    {
        return $this->productAttachmentRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->productAttachmentRepository->resetCriteria();

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
