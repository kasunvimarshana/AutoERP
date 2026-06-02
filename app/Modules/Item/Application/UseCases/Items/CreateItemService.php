<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\Items;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\Items\CreateItemServiceInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class CreateItemService implements CreateItemServiceInterface
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

    public function execute(array $payload): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] ??= $this->currentOrganizationUnit->currentOrganizationUnitId();
            $this->normalizeDefaults($payload);
            $nested = $this->extractNestedPayload($payload);

            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            $record = $this->repository->transaction(function () use ($payload, $tenantId, $nested) {
                $created = $this->repository->create($payload);
                $itemId = (int) $created->id();

                $this->nestedRelationsService->syncForItem($tenantId, $itemId, $payload, $nested, false);

                return $this->repository->findByIdInTenant($itemId, $tenantId) ?? $created;
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

    /**
     * @param array<string, mixed> $payload
     */
    private function normalizeDefaults(array &$payload): void
    {
        $payload['minimum_stock'] = isset($payload['minimum_stock']) && $payload['minimum_stock'] !== ''
            ? $payload['minimum_stock']
            : 0;
        $payload['reorder_point'] = isset($payload['reorder_point']) && $payload['reorder_point'] !== ''
            ? $payload['reorder_point']
            : 0;
        $payload['safety_stock'] = isset($payload['safety_stock']) && $payload['safety_stock'] !== ''
            ? $payload['safety_stock']
            : 0;
        $payload['lead_time_days'] = isset($payload['lead_time_days']) && $payload['lead_time_days'] !== ''
            ? $payload['lead_time_days']
            : 0;
        $payload['review_period_days'] = isset($payload['review_period_days']) && $payload['review_period_days'] !== ''
            ? $payload['review_period_days']
            : 30;
    }
}
