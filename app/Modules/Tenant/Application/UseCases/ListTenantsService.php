<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Contracts\UseCases\ListTenantsServiceInterface;
use Modules\Tenant\Application\DTOs\TenantQueryData;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Throwable;

final class ListTenantsService implements ListTenantsServiceInterface
{
    public function __construct(private readonly TenantRepositoryInterface $tenants)
    {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function execute(array $filters): Result
    {
        try {
            $query = TenantQueryData::fromArray($filters);

            return Result::success($this->tenants->pageByFilters(
                $query->status,
                $query->isActive,
                $query->search,
                $query->perPage,
                $query->page,
            ));
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
