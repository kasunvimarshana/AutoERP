<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ComboItems;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ComboItems\CreateComboItemServiceInterface;
use Modules\Item\Application\Repositories\ComboItemRepositoryInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class CreateComboItemService implements CreateComboItemServiceInterface
{
    public function __construct(
        private readonly ComboItemRepositoryInterface $repository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
    ) {
    }

    public function execute(array $payload): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $comboItemId = (int) ($payload['combo_item_id'] ?? 0);
            $componentItemId = (int) ($payload['component_item_id'] ?? 0);

            if ($comboItemId < 1 || $componentItemId < 1) {
                return Result::failure(
                    new Error(ItemErrorCode::INVALID_VALUE, 'Combo and component item are required.'),
                );
            }

            if ($this->itemRepository->findByIdInTenant($comboItemId, $tenantId) === null) {
                return Result::failure(
                    new Error(ItemErrorCode::INVALID_VALUE, 'Combo item must exist in the current tenant.'),
                );
            }

            if ($this->itemRepository->findByIdInTenant($componentItemId, $tenantId) === null) {
                return Result::failure(
                    new Error(ItemErrorCode::INVALID_VALUE, 'Component item must exist in the current tenant.'),
                );
            }

            if ($this->repository->introducesCycle($tenantId, $comboItemId, $componentItemId)) {
                return Result::failure(
                    new Error(ItemErrorCode::INVALID_VALUE, 'Combo component would create a circular dependency.'),
                );
            }

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] ??= $this->currentOrganizationUnit->currentOrganizationUnitId();

            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
