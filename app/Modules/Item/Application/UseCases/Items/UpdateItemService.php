<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\Items;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\Items\UpdateItemServiceInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class UpdateItemService implements UpdateItemServiceInterface
{
    private const NESTED_KEYS = [
        'attributes',
        'variants',
        'combo_items',
        'uom_conversions',
        'metadata_values',
    ];

    public function __construct(
        private readonly ItemRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly ItemNestedRelationsService $nestedRelationsService,
    ) {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            if ($this->repository->findByIdInTenant($id, $tenantId) === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'Item not found.'));
            }

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] ??= $this->currentOrganizationUnit->currentOrganizationUnitId();
            $nested = $this->extractNestedPayload($payload);

            $record = $this->repository->transaction(function () use ($id, $payload, $tenantId, $nested) {
                $updated = $this->repository->update($id, $payload);
                $itemId = (int) $updated->id();

                $this->nestedRelationsService->syncForItem($tenantId, $itemId, $payload, $nested, true);

                return $this->repository->findByIdInTenant($itemId, $tenantId) ?? $updated;
            });

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function extractNestedPayload(array &$payload): array
    {
        $nested = [];

        foreach (self::NESTED_KEYS as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $nested[$key] = $payload[$key];
            unset($payload[$key]);
        }

        return $nested;
    }
}
