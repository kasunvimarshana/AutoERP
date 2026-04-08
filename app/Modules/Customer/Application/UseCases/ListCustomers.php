<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Customer\Application\Contracts\CreateCustomerServiceInterface;

class ListCustomers
{
    public function __construct(private readonly CreateCustomerServiceInterface $service) {}

    public function execute(array $filters = [], int $perPage = 15, int $page = 1, ?string $sort = null): LengthAwarePaginator
    {
        return $this->service->list($filters, $perPage, $page, $sort);
    }
}
