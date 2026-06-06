<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class PurchaseOrderResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'purchase_order_number' => $this->purchase_order_number,
            'purchase_order_date' => $this->purchase_order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => str((string) $this->enumValue($this->status))->replace('_', ' ')->title()->toString(),
            'supplier_type' => $this->supplier_type,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', fn () => $this->summary($this->supplier, ['supplier_number', 'code', 'name', 'display_name'])),
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location_id' => $this->warehouse_location_id,
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'name', 'symbol'])),
            'exchange_rate' => (string) $this->exchange_rate,
            'subtotal' => (string) $this->subtotal,
            'discount_total' => (string) $this->discount_total,
            'tax_total' => (string) $this->tax_total,
            'charge_total' => (string) $this->charge_total,
            'adjustment_total' => (string) $this->adjustment_total,
            'header_increase_total' => (string) $this->header_increase_total,
            'header_decrease_total' => (string) $this->header_decrease_total,
            'grand_total' => (string) $this->grand_total,
            'notes' => $this->notes,
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->userSummary($this->createdBy)),
            'approved_by' => $this->whenLoaded('approvedBy', fn () => $this->userSummary($this->approvedBy)),
            'approved_at' => $this->approved_at?->toISOString(),
            'closed_by' => $this->whenLoaded('closedBy', fn () => $this->userSummary($this->closedBy)),
            'closed_at' => $this->closed_at?->toISOString(),
            'received_quantity' => $this->quantitySum('received_quantity'),
            'invoiced_quantity' => $this->quantitySum('invoiced_quantity'),
            'returned_quantity' => $this->quantitySum('returned_quantity'),
            'lines' => $this->whenLoaded('lines', fn () => PurchaseOrderLineResource::collection($this->lines)->resolve($request), []),
            'adjustments' => $this->whenLoaded('adjustments', fn () => PurchaseHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    private function summary(mixed $model, array $fields): ?array
    {
        if ($model === null) {
            return null;
        }

        $data = ['id' => (int) $model->getKey()];
        foreach ($fields as $field) {
            if ($model->{$field} ?? null) {
                $data[$field] = $model->{$field};
            }
        }

        if (! isset($data['name']) && isset($data['display_name'])) {
            $data['name'] = $data['display_name'];
        }

        return $data;
    }

    private function userSummary(mixed $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => (int) $user->getKey(),
            'name' => $user->name ?? $user->full_name ?? $user->email ?? 'User #'.$user->getKey(),
            'email' => $user->email ?? null,
        ];
    }

    private function quantitySum(string $column): string
    {
        if (! $this->relationLoaded('lines')) {
            return '0.000000';
        }

        $total = '0.000000';
        foreach ($this->lines as $line) {
            $total = app(\Modules\Core\Services\DecimalMath::class)->add($total, (string) $line->{$column});
        }

        return $total;
    }
}
