<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class ListTenants
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo
    ) {}

    /** @var array<string> Allowed filter fields to prevent unauthorized data access */
    private const ALLOWED_FILTERS = ['name', 'domain', 'active'];

    public function execute(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $repo = clone $this->tenantRepo;
        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repo->where($field, $value);
            }
        }

        return $repo->paginate($perPage, ['*'], 'page', $page);
    }
}
