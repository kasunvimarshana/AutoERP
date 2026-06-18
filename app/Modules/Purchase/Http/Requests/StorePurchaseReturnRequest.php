<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Illuminate\Validation\Validator;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\DTOs\PurchaseReturnLineData;
use Modules\Purchase\Enums\PurchaseReturnType;

final class StorePurchaseReturnRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'return_date' => ['required', 'date'],
            'warehouse_id' => ['required_if:return_type,manual_supplier_return', 'prohibited_unless:return_type,manual_supplier_return', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'prohibited_unless:return_type,manual_supplier_return', 'integer', 'min:1'],
            'return_number' => ['nullable', 'string', 'max:100'],
            'supplier_type' => ['nullable', 'prohibited_unless:return_type,manual_supplier_return', 'string', 'max:150'],
            'supplier_id' => ['required_if:return_type,manual_supplier_return', 'prohibited_unless:return_type,manual_supplier_return', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'return_type' => ['nullable', 'in:referenced,manual_supplier_return'],
            'source_type' => ['prohibited'],
            'source_id' => ['required_unless:return_type,manual_supplier_return', 'prohibited_if:return_type,manual_supplier_return', 'integer', 'min:1'],
            'approval_required' => ['prohibited'],
            'affects_supplier_balance' => ['prohibited'],
            'cost_basis' => ['required_if:return_type,manual_supplier_return', 'prohibited_unless:return_type,manual_supplier_return', 'decimal:0,6', 'min:0'],
            'audit_metadata' => ['nullable', 'array'],
            'audit_metadata.reference' => ['nullable', 'string', 'max:100'],
            'audit_metadata.notes' => ['nullable', 'string', 'max:1000'],
            'audit_metadata.attachments' => ['nullable', 'array'],
            'audit_metadata.attachments.*' => ['string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.source_line_type' => ['required', 'in:goods_receipt_note_line,manual_supplier_return'],
            'lines.*.source_line_id' => ['required', 'integer', 'min:0'],
            'lines.*.returned_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.item_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.cost_basis' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.reason' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $returnType = (string) $this->input('return_type', 'referenced');
            $lines = $this->input('lines', []);
            $seen = [];
            $auditMetadata = $this->input('audit_metadata', []);
            if (is_array($auditMetadata)) {
                $allowedAuditKeys = ['reference', 'notes', 'attachments'];
                foreach (array_keys($auditMetadata) as $key) {
                    if (! in_array((string) $key, $allowedAuditKeys, true)) {
                        $validator->errors()->add('audit_metadata', 'Purchase return audit metadata contains an unsupported key.');
                    }
                }
            }

            foreach ($lines as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }

                $sourceLineType = (string) ($line['source_line_type'] ?? '');
                $sourceLineId = (int) ($line['source_line_id'] ?? 0);
                $duplicateKey = $sourceLineType.':'.$sourceLineId;
                if (isset($seen[$duplicateKey])) {
                    $validator->errors()->add("lines.{$index}.source_line_id", 'Duplicate purchase return source line.');
                }
                $seen[$duplicateKey] = true;

                if ($returnType === 'manual_supplier_return') {
                    if ($sourceLineType !== 'manual_supplier_return') {
                        $validator->errors()->add("lines.{$index}.source_line_type", 'Manual supplier returns require manual return lines.');
                    }
                    foreach (['item_id', 'uom_id', 'cost_basis'] as $field) {
                        if (! array_key_exists($field, $line) || $line[$field] === null || $line[$field] === '') {
                            $validator->errors()->add("lines.{$index}.{$field}", 'Manual supplier return lines require item, UOM, and cost basis.');
                        }
                    }

                    continue;
                }

                if ($sourceLineType !== 'goods_receipt_note_line') {
                    $validator->errors()->add("lines.{$index}.source_line_type", 'Referenced purchase returns require goods receipt note lines.');
                }
                if ($sourceLineId < 1) {
                    $validator->errors()->add("lines.{$index}.source_line_id", 'Referenced purchase returns require a goods receipt line.');
                }
                foreach (['item_id', 'item_variant_id', 'uom_id', 'unit_price', 'cost_basis'] as $field) {
                    if (array_key_exists($field, $line)) {
                        $validator->errors()->add("lines.{$index}.{$field}", 'Referenced purchase return line details are derived from the source receipt.');
                    }
                }
            }
        });
    }

    public function toData(): CreatePurchaseReturnData
    {
        return new CreatePurchaseReturnData(
            tenantId: $this->tenantId(),
            returnDate: (string) $this->input('return_date'),
            warehouseId: $this->intOrNull('warehouse_id'),
            organizationUnitId: $this->organizationUnitId(),
            returnNumber: $this->filled('return_number') ? (string) $this->input('return_number') : null,
            warehouseLocationId: $this->filled('warehouse_location_id') ? (int) $this->input('warehouse_location_id') : null,
            supplierType: $this->filled('supplier_type') ? (string) $this->input('supplier_type') : null,
            supplierId: $this->filled('supplier_id') ? (int) $this->input('supplier_id') : null,
            reason: $this->filled('reason') ? (string) $this->input('reason') : null,
            returnType: PurchaseReturnType::from((string) $this->input('return_type', 'referenced')),
            sourceType: $this->filled('source_type') ? (string) $this->input('source_type') : null,
            sourceId: $this->filled('source_id') ? (int) $this->input('source_id') : null,
            approvalRequired: false,
            affectsSupplierBalance: true,
            costBasis: $this->filled('cost_basis') ? (string) $this->input('cost_basis') : null,
            auditMetadata: $this->input('audit_metadata'),
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): PurchaseReturnLineData => new PurchaseReturnLineData(
                sourceLineType: (string) $row['source_line_type'],
                sourceLineId: (int) $row['source_line_id'],
                returnedQuantity: (string) $row['returned_quantity'],
                itemId: isset($row['item_id']) ? (int) $row['item_id'] : null,
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                unitPrice: isset($row['unit_price']) ? (string) $row['unit_price'] : null,
                costBasis: isset($row['cost_basis']) ? (string) $row['cost_basis'] : null,
                reason: $row['reason'] ?? null,
            ), $this->input('lines')),
        );
    }
}
