<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Illuminate\Validation\Validator;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\DTOs\PurchaseReturnLineData;
use Modules\Purchase\Enums\PurchaseReturnType;

final class StoreManualSupplierReturnRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'return_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'return_number' => ['nullable', 'string', 'max:100'],
            'supplier_type' => ['prohibited'],
            'supplier_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
            'return_type' => ['nullable', 'in:manual_supplier_return'],
            'source_type' => ['prohibited'],
            'source_id' => ['prohibited'],
            'approval_required' => ['prohibited'],
            'affects_supplier_balance' => ['prohibited'],
            'cost_basis' => ['nullable', 'decimal:0,6', 'min:0'],
            'audit_metadata' => ['nullable', 'array'],
            'audit_metadata.reference' => ['nullable', 'string', 'max:100'],
            'audit_metadata.notes' => ['nullable', 'string', 'max:1000'],
            'audit_metadata.attachments' => ['nullable', 'array'],
            'audit_metadata.attachments.*' => ['string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.client_line_key' => ['required', 'string', 'max:100'],
            'lines.*.source_line_type' => ['prohibited'],
            'lines.*.source_line_id' => ['prohibited'],
            'lines.*.returned_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.cost_basis' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.reason' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $seenKeys = [];
            $auditMetadata = $this->input('audit_metadata', []);
            if (is_array($auditMetadata)) {
                $allowedAuditKeys = ['reference', 'notes', 'attachments'];
                foreach (array_keys($auditMetadata) as $key) {
                    if (! in_array((string) $key, $allowedAuditKeys, true)) {
                        $validator->errors()->add('audit_metadata', 'Purchase return audit metadata contains an unsupported key.');
                    }
                }
            }

            foreach ($this->input('lines', []) as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }

                $clientLineKey = trim((string) ($line['client_line_key'] ?? ''));
                if ($clientLineKey === '') {
                    continue;
                }

                if (isset($seenKeys[$clientLineKey])) {
                    $validator->errors()->add("lines.{$index}.client_line_key", 'Duplicate manual return line key.');
                }
                $seenKeys[$clientLineKey] = true;
            }
        });
    }

    public function toData(): CreatePurchaseReturnData
    {
        $lines = $this->input('lines');

        return new CreatePurchaseReturnData(
            tenantId: $this->tenantId(),
            returnDate: (string) $this->input('return_date'),
            warehouseId: $this->intOrNull('warehouse_id'),
            organizationUnitId: $this->organizationUnitId(),
            returnNumber: $this->filled('return_number') ? (string) $this->input('return_number') : null,
            warehouseLocationId: $this->filled('warehouse_location_id') ? (int) $this->input('warehouse_location_id') : null,
            supplierType: null,
            supplierId: $this->filled('supplier_id') ? (int) $this->input('supplier_id') : null,
            reason: $this->filled('reason') ? (string) $this->input('reason') : null,
            returnType: PurchaseReturnType::ManualSupplierReturn,
            sourceType: 'manual_supplier_return',
            sourceId: null,
            approvalRequired: true,
            affectsSupplierBalance: true,
            costBasis: $this->filled('cost_basis') ? (string) $this->input('cost_basis') : null,
            auditMetadata: $this->input('audit_metadata'),
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): PurchaseReturnLineData => new PurchaseReturnLineData(
                sourceLineType: null,
                sourceLineId: null,
                returnedQuantity: (string) $row['returned_quantity'],
                itemId: (int) $row['item_id'],
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                uomId: (int) $row['uom_id'],
                unitPrice: isset($row['unit_price']) ? (string) $row['unit_price'] : null,
                costBasis: (string) $row['cost_basis'],
                reason: $row['reason'] ?? null,
                clientLineKey: (string) $row['client_line_key'],
            ), $lines),
        );
    }
}
