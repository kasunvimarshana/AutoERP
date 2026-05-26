<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Services;

use Illuminate\Support\Facades\DB;

final class VehicleServiceCalculationService
{
    public function recalculateJobCard(int $jobCardId): array
    {
        return DB::transaction(function () use ($jobCardId): array {
            $jobCard = DB::table('vehicle_service_job_cards')->lockForUpdate()->find($jobCardId);
            if ($jobCard === null) {
                throw new \RuntimeException('Job card not found.');
            }

            $inventory = $this->recalculateLines('vehicle_service_job_card_lines', 'job_card_id', $jobCardId);
            $nonInventory = $this->recalculateLines('vehicle_service_non_inventory_items', 'job_card_id', $jobCardId);
            $labor = $this->recalculateLaborItems($jobCardId);
            $this->recalculateLaborAssignments($jobCardId);

            $subtotal = $inventory['subtotal'] + $nonInventory['subtotal'] + $labor['subtotal'];
            $lineDiscount = $inventory['discount'] + $nonInventory['discount'] + $labor['discount'];
            $lineTax = $inventory['tax'] + $nonInventory['tax'] + $labor['tax'];
            $headerDiscount = $this->discountAmount($subtotal - $lineDiscount, $jobCard->header_discount_type, (float) ($jobCard->header_discount_value ?? 0));
            $headerTax = $this->taxAmount(max(0, $subtotal - $lineDiscount - $headerDiscount), $jobCard->header_tax_group_id === null ? null : (int) $jobCard->header_tax_group_id);
            $discountTotal = round($lineDiscount + $headerDiscount, 4);
            $taxTotal = round($lineTax + $headerTax, 4);
            $grandTotal = round($subtotal - $discountTotal + $taxTotal + (float) $jobCard->debit_note_total - (float) $jobCard->credit_note_total, 4);

            DB::table('vehicle_service_job_cards')->where('id', $jobCardId)->update([
                'subtotal' => $inventory['subtotal'],
                'line_tax_total' => $inventory['tax'],
                'line_discount_total' => $inventory['discount'],
                'non_inventory_item_subtotal' => $nonInventory['subtotal'],
                'non_inventory_item_tax_total' => $nonInventory['tax'],
                'non_inventory_item_discount_total' => $nonInventory['discount'],
                'labor_item_subtotal' => $labor['subtotal'],
                'labor_item_tax_total' => $labor['tax'],
                'labor_item_discount_total' => $labor['discount'],
                'header_discount_amount' => $headerDiscount,
                'header_tax_amount' => $headerTax,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'balance' => round($grandTotal - (float) $jobCard->paid_amount, 4),
                'row_version' => (int) $jobCard->row_version + 1,
                'updated_at' => now(),
            ]);

            return (array) DB::table('vehicle_service_job_cards')->find($jobCardId);
        });
    }

    /** @return array{subtotal: float, discount: float, tax: float} */
    private function recalculateLines(string $table, string $foreignKey, int $jobCardId): array
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;

        foreach (DB::table($table)->where($foreignKey, $jobCardId)->get() as $line) {
            $gross = round((float) $line->quantity * (float) $line->unit_price, 4);
            $lineDiscount = $this->discountAmount($gross, $line->discount_type, (float) $line->discount_value);
            $lineTotal = round($gross - $lineDiscount, 4);
            $lineTax = $this->taxAmount($lineTotal, $line->tax_group_id === null ? null : (int) $line->tax_group_id);

            DB::table($table)->where('id', (int) $line->id)->update([
                'gross_amount' => $gross,
                'discount_amount' => $lineDiscount,
                'line_total' => $lineTotal,
                'tax_amount' => $lineTax,
                'line_total_with_tax' => round($lineTotal + $lineTax, 4),
                'updated_at' => now(),
            ]);

            $subtotal += $gross;
            $discount += $lineDiscount;
            $tax += $lineTax;
        }

        return ['subtotal' => round($subtotal, 4), 'discount' => round($discount, 4), 'tax' => round($tax, 4)];
    }

    /** @return array{subtotal: float, discount: float, tax: float} */
    private function recalculateLaborItems(int $jobCardId): array
    {
        $totals = $this->recalculateLines('vehicle_service_labor_items', 'job_card_id', $jobCardId);

        foreach (DB::table('vehicle_service_labor_items')->where('job_card_id', $jobCardId)->get() as $line) {
            DB::table('vehicle_service_labor_items')->where('id', (int) $line->id)->update([
                'incentive_amount' => $this->incentiveAmount((float) $line->line_total, $line->incentive_type, (float) $line->incentive_value),
                'updated_at' => now(),
            ]);
        }

        return $totals;
    }

    private function recalculateLaborAssignments(int $jobCardId): void
    {
        foreach (DB::table('vehicle_service_labor_assignments')->where('job_card_id', $jobCardId)->get() as $assignment) {
            $base = (float) ($assignment->hours_worked ?? 0) * (float) ($assignment->hourly_rate ?? 0);
            DB::table('vehicle_service_labor_assignments')->where('id', (int) $assignment->id)->update([
                'incentive_amount' => $this->incentiveAmount($base, $assignment->incentive_type, (float) $assignment->incentive_value),
                'updated_at' => now(),
            ]);
        }
    }

    private function discountAmount(float $base, ?string $type, float $value): float
    {
        return match ($type) {
            'percentage' => round($base * ($value / 100), 4),
            'fixed' => round(min($base, $value), 4),
            default => 0.0,
        };
    }

    private function incentiveAmount(float $base, ?string $type, float $value): float
    {
        return match ($type) {
            'percentage' => round($base * ($value / 100), 4),
            'fixed' => round($value, 4),
            default => 0.0,
        };
    }

    private function taxAmount(float $base, ?int $taxGroupId): float
    {
        if ($taxGroupId === null || $base <= 0) {
            return 0.0;
        }

        $tax = 0.0;
        $taxableBase = $base;
        foreach (DB::table('tax_rates')->where('tax_group_id', $taxGroupId)->where('is_active', true)->orderBy('is_compound')->get() as $rate) {
            $amount = strtoupper((string) $rate->type) === 'FIXED'
                ? (float) $rate->rate
                : $taxableBase * ((float) $rate->rate / 100);
            $tax += $amount;
            if ((bool) $rate->is_compound) {
                $taxableBase += $amount;
            }
        }

        return round($tax, 4);
    }
}
