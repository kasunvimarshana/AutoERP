<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ComboItems;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ComboItems\UpdateComboItemServiceInterface;
use Modules\Item\Application\Repositories\ComboItemRepositoryInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Application\Support\ItemUomOptions;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class UpdateComboItemService implements UpdateComboItemServiceInterface
{
    public function __construct(
        private readonly ComboItemRepositoryInterface $repository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $existing = $this->repository->findByIdInTenant($id, $tenantId);
            if ($existing === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ComboItem not found.'));
            }

            $comboItemId = (int) ($payload['combo_item_id'] ?? $existing->get('combo_item_id', 0));
            $componentItemId = (int) ($payload['component_item_id'] ?? $existing->get('component_item_id', 0));

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

            if ($this->repository->introducesCycle($tenantId, $comboItemId, $componentItemId, (int) $id)) {
                return Result::failure(
                    new Error(ItemErrorCode::INVALID_VALUE, 'Combo component would create a circular dependency.'),
                );
            }

            $uomId = (int) ($payload['uom_id'] ?? $existing->get('uom_id', 0));
            if ($uomId < 1 || ! ItemUomOptions::isAllowed($tenantId, $componentItemId, $uomId)) {
                return Result::failure(
                    new Error(ItemErrorCode::INVALID_VALUE, 'The selected UOM is not configured for this component item.'),
                );
            }

            $payload['tenant_id'] = $tenantId;

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
