<?php

declare(strict_types=1);

namespace Modules\Category\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;

class ListCategories
{
    private const ALLOWED_FILTERS = ['tenant_id', 'name', 'slug', 'status', 'parent_id'];

    public function __construct(private readonly CategoryRepositoryInterface $categoryRepo) {}

    public function execute(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $repo = clone $this->categoryRepo;

        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repo->where($field, $value);
            }
        }

        return $repo->paginate($perPage, ['*'], 'page', $page);
    }
}
