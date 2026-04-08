<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;

class ListSuppliers
{
    public function __construct(private readonly CreateSupplierServiceInterface $service) {}

    public function execute(array $filters = [], int $perPage = 15, int $page = 1, ?string $sort = null): LengthAwarePaginator
    {
        return $this->service->list($filters, $perPage, $page, $sort);
    }
}
