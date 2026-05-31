<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemIdentifiers;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemIdentifiers\ListItemIdentifiersServiceInterface;
use Modules\Item\Application\Repositories\ItemIdentifierRepositoryInterface;
use Modules\Item\Domain\Constants\ItemDefaults;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class ListItemIdentifiersService implements ListItemIdentifiersServiceInterface
{
    public function __construct(
        private readonly ItemIdentifierRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $criteria['tenant_id'] = $tenantId;

            $resolvedPage = $page > 0 ? $page : ItemDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('item.pagination.max_per_page', ItemDefaults::MAX_PER_PAGE))
                : (int) config('item.pagination.default_per_page', ItemDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
