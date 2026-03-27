<?php

declare(strict_types=1);

namespace Modules\Product\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class ListProducts
{
    private const ALLOWED_FILTERS = ['tenant_id', 'name', 'category', 'status', 'sku'];

    public function __construct(private readonly ProductRepositoryInterface $productRepo) {}

    public function execute(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $repo = clone $this->productRepo;

        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repo->where($field, $value);
            }
        }

        return $repo->paginate($perPage, ['*'], 'page', $page);
    }
}
