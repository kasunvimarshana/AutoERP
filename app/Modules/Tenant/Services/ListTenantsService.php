<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\DTOs\TenantQueryData;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Throwable;

final class ListTenantsService
{
    public function __construct(private readonly TenantRepositoryInterface $tenants) {}

    /**
     * @param  array<string, mixed>  $filters
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
