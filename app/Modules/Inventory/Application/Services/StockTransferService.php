<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Inventory\Application\Support\InventoryServiceSupport;

final class StockTransferService
{
    public function __construct(
        private readonly InventoryServiceSupport $support,
        private readonly StockIssuingService $issuing,
        private readonly StockReceivingService $receiving,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function transfer(array $payload): array
    {
        if (
            ! isset($payload['from_warehouse_id'], $payload['to_warehouse_id'])
            || (int) $payload['from_warehouse_id'] <= 0
            || (int) $payload['to_warehouse_id'] <= 0
        ) {
            throw ValidationException::withMessages([
                'warehouse_id' => ['Source and destination warehouse IDs are required.'],
            ]);
        }

        if ((int) $payload['from_warehouse_id'] === (int) $payload['to_warehouse_id']
            && ($payload['from_location_id'] ?? null) === ($payload['to_location_id'] ?? null)) {
            throw ValidationException::withMessages([
                'destination' => ['Source and destination must be different.'],
            ]);
        }

        return DB::transaction(function () use ($payload): array {
            $tenantId = $this->support->tenantId($payload);
            $this->support->validateWarehouseScope(
                $tenantId,
                [(int) $payload['from_warehouse_id'], (int) $payload['to_warehouse_id']],
                [
                    isset($payload['from_location_id']) ? (int) $payload['from_location_id'] : null,
                    isset($payload['to_location_id']) ? (int) $payload['to_location_id'] : null,
                ],
            );
            $requestedBy = $payload['requested_by'] ?? $this->currentUser->currentUserId();
            if ($requestedBy === null) {
                throw ValidationException::withMessages([
                    'requested_by' => ['Requested by user ID is required for stock transfers.'],
                ]);
            }

            $transferId = DB::table('stock_transfers')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->support->organizationUnitId($payload),
                'reference_number' => $payload['reference_number'] ?? 'TRF-'.now()->format('YmdHisv'),
                'from_warehouse_id' => (int) $payload['from_warehouse_id'],
                'to_warehouse_id' => (int) $payload['to_warehouse_id'],
                'from_location_id' => $payload['from_location_id'] ?? null,
                'to_location_id' => $payload['to_location_id'] ?? null,
                'status' => 'COMPLETED',
                'requested_by' => (int) $requestedBy,
                'approved_by' => $payload['approved_by'] ?? null,
                'transferred_at' => now(),
                'notes' => $payload['notes'] ?? null,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $issueLines = [];
            $receiveLines = [];
            $lines = $this->support->normalizeLines([...$payload, 'warehouse_id' => $payload['from_warehouse_id'], 'location_id' => $payload['from_location_id'] ?? null]);
            foreach ($lines as $index => $line) {
                $issueLines[] = [...$line, 'warehouse_id' => (int) $payload['from_warehouse_id'], 'location_id' => $payload['from_location_id'] ?? null, 'source_line_id' => $index + 1];
                $receiveLines[] = [...$line, 'warehouse_id' => (int) $payload['to_warehouse_id'], 'location_id' => $payload['to_location_id'] ?? null, 'source_line_id' => $index + 1];
            }

            $out = $this->issuing->issue([...$payload, 'source_type' => 'stock_transfer', 'source_id' => $transferId, 'movement_type' => 'TRANSFER_OUT', 'lines' => $issueLines]);
            $in = $this->receiving->receive([...$payload, 'source_type' => 'stock_transfer', 'source_id' => $transferId, 'movement_type' => 'TRANSFER_IN', 'lines' => $receiveLines]);
            $this->insertTransferLines($transferId, $payload, $lines, $out['movements'], $in['movements']);

            return ['transfer_id' => $transferId, 'outgoing' => $out['movements'], 'incoming' => $in['movements']];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array<string, mixed>>  $outgoing
     * @param  array<int, array<string, mixed>>  $incoming
     */
    private function insertTransferLines(int $transferId, array $payload, array $lines, array $outgoing, array $incoming): void
    {
        $tenantId = $this->support->tenantId($payload);
        $organizationUnitId = $this->support->organizationUnitId($payload);

        foreach ($lines as $index => $line) {
            DB::table('stock_transfer_lines')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'stock_transfer_id' => $transferId,
                'item_id' => (int) $line['item_id'],
                'variant_id' => $line['variant_id'] ?? null,
                'batch_id' => $line['batch_id'] ?? null,
                'serial_id' => $line['serial_id'] ?? null,
                'from_location_id' => $payload['from_location_id'] ?? null,
                'to_location_id' => $payload['to_location_id'] ?? null,
                'uom_id' => (int) $line['uom_id'],
                'quantity' => (float) $line['quantity'],
                'base_quantity' => (float) ($outgoing[$index]['base_quantity'] ?? $line['quantity']),
                'unit_cost' => $outgoing[$index]['unit_cost'] ?? $line['unit_cost'] ?? null,
                'notes' => $line['notes'] ?? null,
                'outgoing_movement_id' => $outgoing[$index]['movement_id'] ?? null,
                'incoming_movement_id' => $incoming[$index]['movement_id'] ?? null,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
