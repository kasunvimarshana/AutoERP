<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class VehicleServiceLineRuleService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function assertBelongsToJob(VehicleServiceJob $job, VehicleServiceJobLine $line): void
    {
        if ((int) $line->vehicle_service_job_id !== (int) $job->getKey()) {
            throw new InvalidArgumentException('Service job line does not belong to the service job.');
        }
    }

    public function assertCanUpdate(
        VehicleServiceJobLine $line,
        VehicleServiceLineData $data,
    ): void {
        if ($line->inventory_movement_id !== null) {
            throw new InvalidArgumentException('Issued inventory lines cannot be edited.');
        }
        if ($line->line_source_type === VehicleServiceLineSourceType::ComboChild) {
            throw new InvalidArgumentException('Combo child lines are managed through their combo parent.');
        }
        if ($line->children()->whereNotNull('inventory_movement_id')->exists()) {
            throw new InvalidArgumentException('Combo parents with issued inventory children cannot be edited.');
        }
        if ($line->children()->exists() && (
            $data->lineSourceType !== VehicleServiceLineSourceType::ComboParent
            || $data->itemId !== (int) $line->item_id
            || $this->math->compare($data->quantity, (string) $line->quantity) !== 0
        )) {
            throw new InvalidArgumentException(
                'Expanded combo item and quantity cannot be changed. Remove and add the combo again.',
            );
        }
    }

    public function assertCanDelete(VehicleServiceJobLine $line): void
    {
        if ($line->inventory_movement_id !== null
            || $line->children()->whereNotNull('inventory_movement_id')->exists()) {
            throw new InvalidArgumentException('Issued inventory lines cannot be deleted.');
        }
        if ($line->line_source_type === VehicleServiceLineSourceType::ComboChild) {
            throw new InvalidArgumentException('Combo child lines are managed through their combo parent.');
        }
    }

    public function isInventoryIssueLine(VehicleServiceJobLine $line): bool
    {
        $line->loadMissing('item');

        return $line->is_inventory_tracked
            && ! $line->is_customer_supplied
            && ! $line->is_external
            && ($line->line_source_type === VehicleServiceLineSourceType::InventoryItem
                || ($line->line_source_type === VehicleServiceLineSourceType::ComboChild
                    && (bool) $line->item?->is_stockable));
    }

    /** @return array{inventory: bool, employee: bool} */
    public function flags(VehicleServiceLineSourceType $source, ?Item $item): array
    {
        return match ($source) {
            VehicleServiceLineSourceType::InventoryItem => ['inventory' => true, 'employee' => false],
            VehicleServiceLineSourceType::ExternalItem => ['inventory' => false, 'employee' => false],
            VehicleServiceLineSourceType::ServiceItem,
            VehicleServiceLineSourceType::LabourItem => ['inventory' => false, 'employee' => true],
            VehicleServiceLineSourceType::ComboParent => ['inventory' => false, 'employee' => false],
            VehicleServiceLineSourceType::ComboChild => [
                'inventory' => (bool) $item?->is_stockable,
                'employee' => in_array($item?->item_type, [ItemType::Service, ItemType::Labour], true),
            ],
        };
    }
}
