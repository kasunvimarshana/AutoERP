<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Idempotency\Enums\IdempotencyStatus;
use Modules\Idempotency\Services\IdempotencyService;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Enums\ItemType;

final class ConsumableUsageService
{
    private const SOURCE_TYPE = 'internal_consumption';

    private const SOURCE_MODULE = 'inventory';

    private const IDEMPOTENCY_OPERATION = 'inventory.consumable_usage.post';

    private const ZERO = '0.000000';

    public function __construct(
        private readonly StockMovementService $stock,
        private readonly InventoryValidationService $validation,
        private readonly DecimalMath $math,
        private readonly FinancePostingInterface $finance,
        private readonly FinanceSourceReversalInterface $reversals,
        private readonly IdempotencyService $idempotency,
    ) {}

    /** @param array<string, mixed> $data */
    public function post(array $data, int $tenantId, ?int $organizationUnitId, int $actorId, string $idempotencyKey): InventoryMovement
    {
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Idempotency-Key header is required.');
        }

        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId, $actorId, $idempotencyKey): InventoryMovement {
            $record = $this->idempotency->acquire(
                $tenantId,
                $organizationUnitId,
                self::IDEMPOTENCY_OPERATION,
                hash('sha256', $idempotencyKey),
                hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)),
                createdBy: $actorId,
            );
            if (! $record->wasRecentlyCreated) {
                if ($record->status !== IdempotencyStatus::Completed) {
                    throw new LogicException('Consumable usage with this key is still in progress.');
                }

                $movementId = $record->document_ids['movement_id'] ?? null;
                if (! is_numeric($movementId)) {
                    throw new LogicException('Completed consumable usage has no movement identifier.');
                }

                return $this->scopedMovement($tenantId, $organizationUnitId, (int) $movementId);
            }

            $item = $this->validation->item($tenantId, $organizationUnitId, (int) $data['item_id']);
            if ($item->item_type !== ItemType::Consumable || ! $item->is_stockable) {
                throw new InvalidArgumentException('Select an active stockable consumable item.');
            }

            $movement = $this->stock->record(new StockMovementData(
                tenantId: $tenantId,
                movementDate: (string) $data['usage_date'],
                movementType: InventoryMovementType::InternalConsumption,
                direction: InventoryDirection::Out,
                itemId: (int) $data['item_id'],
                warehouseId: (int) $data['warehouse_id'],
                quantity: (string) $data['quantity'],
                organizationUnitId: $organizationUnitId,
                itemVariantId: isset($data['item_variant_id']) ? (int) $data['item_variant_id'] : null,
                warehouseLocationId: isset($data['warehouse_location_id']) ? (int) $data['warehouse_location_id'] : null,
                batchId: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
                description: $data['notes'] ?? null,
                createdBy: $actorId,
                uomId: isset($data['uom_id']) ? (int) $data['uom_id'] : null,
            ), $actorId);

            $amount = (string) $movement->total_cost;
            if ($this->math->compare($amount, self::ZERO) > 0) {
                $this->finance->post(new PostingContext(
                    source: new PostingSourceData(
                        sourceType: self::SOURCE_TYPE,
                        sourceId: (int) $movement->getKey(),
                        tenantId: $tenantId,
                        organizationUnitId: $organizationUnitId,
                        sourceModule: self::SOURCE_MODULE,
                        sourceNumber: (string) $movement->movement_number,
                        sourceDate: (string) $data['usage_date'],
                    ),
                    postingDate: (string) $data['usage_date'],
                    lines: [
                        new PostingLine(debit: $amount, credit: self::ZERO, profileKey: FinanceAccountRoleCode::OperatingExpense->value),
                        new PostingLine(debit: self::ZERO, credit: $amount, profileKey: FinanceAccountRoleCode::Inventory->value),
                    ],
                    description: 'Consumable usage '.$movement->movement_number,
                    postingProfileCode: FinancePostingProfileCode::InternalConsumption->value,
                ), $actorId);
            }

            $this->idempotency->complete($record, ['movement_id' => (int) $movement->getKey()], ['movement_id' => (int) $movement->getKey()]);

            return $movement;
        }, 3);
    }

    public function reverse(int $tenantId, ?int $organizationUnitId, int $movementId, int $actorId, string $reason): InventoryMovement
    {
        return DB::transaction(function () use ($tenantId, $organizationUnitId, $movementId, $actorId, $reason): InventoryMovement {
            $movement = $this->scopedMovement($tenantId, $organizationUnitId, $movementId);
            if ($movement->movement_type !== InventoryMovementType::InternalConsumption) {
                throw new InvalidArgumentException('Only consumable usage can be reversed here.');
            }

            $this->stock->reverse($movement, $actorId, $reason);
            if ($this->math->compare((string) $movement->total_cost, self::ZERO) > 0) {
                $this->reversals->reverseSource(
                    $tenantId,
                    $organizationUnitId,
                    self::SOURCE_MODULE,
                    self::SOURCE_TYPE,
                    $movementId,
                    now()->toDateString(),
                    $actorId,
                    $reason,
                );
            }

            return $movement->refresh();
        }, 3);
    }

    private function scopedMovement(int $tenantId, ?int $organizationUnitId, int $movementId): InventoryMovement
    {
        $query = InventoryMovement::query()->where('tenant_id', $tenantId);
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        return $query->findOrFail($movementId);
    }
}
