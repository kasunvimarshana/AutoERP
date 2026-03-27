<?php

declare(strict_types=1);

namespace Modules\Brand\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;

class ListBrands
{
    private const ALLOWED_FILTERS = ['tenant_id', 'name', 'slug', 'status'];

    public function __construct(private readonly BrandRepositoryInterface $brandRepo) {}

    public function execute(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $repo = clone $this->brandRepo;

        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repo->where($field, $value);
            }
        }

        return $repo->paginate($perPage, ['*'], 'page', $page);
    }
}
