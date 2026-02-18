<?php

declare(strict_types=1);

namespace Modules\POS\Services;

use Modules\POS\Models\StockAdjustment;
use Modules\POS\Models\StockAdjustmentLine;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function __construct(
        private ReferenceNumberService $referenceNumberService
    ) {}

    public function createAdjustment(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $referenceNumber = $this->referenceNumberService->generate('adjustment');

            $adjustment = StockAdjustment::create([
                'location_id' => $data['location_id'],
                'reference_number' => $referenceNumber,
                'adjustment_date' => $data['adjustment_date'] ?? now(),
                'type' => $data['type'],
                'total_amount' => $data['total_amount'] ?? 0,
                'reason' => $data['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create adjustment lines
            if (isset($data['lines'])) {
                foreach ($data['lines'] as $line) {
                    $this->createAdjustmentLine($adjustment, $line);
                }
            }

            return $adjustment->fresh(['lines']);
        });
    }

    private function createAdjustmentLine(StockAdjustment $adjustment, array $lineData): StockAdjustmentLine
    {
        return StockAdjustmentLine::create([
            'adjustment_id' => $adjustment->id,
            'product_id' => $lineData['product_id'],
            'variation_id' => $lineData['variation_id'] ?? null,
            'quantity' => $lineData['quantity'],
            'unit_cost' => $lineData['unit_cost'],
            'line_total' => $lineData['line_total'],
            'lot_number' => $lineData['lot_number'] ?? null,
        ]);
    }

    public function updateAdjustment(StockAdjustment $adjustment, array $data): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $data) {
            $adjustment->update([
                'type' => $data['type'] ?? $adjustment->type,
                'total_amount' => $data['total_amount'] ?? $adjustment->total_amount,
                'reason' => $data['reason'] ?? $adjustment->reason,
            ]);

            // Update lines if provided
            if (isset($data['lines'])) {
                $adjustment->lines()->delete();
                foreach ($data['lines'] as $line) {
                    $this->createAdjustmentLine($adjustment, $line);
                }
            }

            return $adjustment->fresh(['lines']);
        });
    }
}
