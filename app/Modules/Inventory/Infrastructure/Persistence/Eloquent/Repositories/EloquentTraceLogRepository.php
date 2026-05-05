<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Domain\RepositoryInterfaces\TraceLogRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TraceLogModel;

class EloquentTraceLogRepository implements TraceLogRepositoryInterface
{
    private string $traceLogsTable;

    public function __construct(TraceLogModel $traceLogModel)
    {
        $this->traceLogsTable = $traceLogModel->getTable();
    }

    public function recordForMovement(StockMovement $movement): void
    {
        $metadata = $movement->getMetadata();

        DB::table($this->traceLogsTable)->insert([
            'tenant_id' => $movement->getTenantId(),
            'entity_type' => 'product',
            'entity_id' => $movement->getProductId(),
            'identifier_id' => null,
            'action_type' => $this->mapActionType($movement->getMovementType()),
            'reference_type' => $movement->getReferenceType(),
            'reference_id' => $movement->getReferenceId(),
            'source_location_id' => $movement->getFromLocationId(),
            'destination_location_id' => $movement->getToLocationId(),
            'quantity' => $movement->getQuantity(),
            'performed_by' => $movement->getPerformedBy(),
            'performed_at' => $movement->getPerformedAt() ?? now(),
            'device_id' => is_array($metadata) ? ($metadata['device_id'] ?? null) : null,
            'metadata' => is_array($metadata) ? json_encode($metadata, JSON_THROW_ON_ERROR) : null,
        ]);
    }

    private function mapActionType(string $movementType): string
    {
        return match ($movementType) {
            'receipt', 'return_in' => 'receive',
            'shipment', 'return_out', 'write_off' => 'ship',
            'transfer' => 'transfer',
            'adjustment', 'adjustment_in', 'adjustment_out', 'cycle_count' => 'adjust',
            default => 'scan',
        };
    }
}
