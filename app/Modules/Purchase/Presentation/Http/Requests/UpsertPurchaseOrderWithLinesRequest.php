<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;
use Modules\Item\Application\Support\ItemUomOptions;

final class UpsertPurchaseOrderWithLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => $this->withExists($required, 'tenants'),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => $this->withExists(['nullable'], 'organization_units'),
            'metadata' => ['nullable', 'array'],
            'reference' => ['nullable', 'string', 'max:255'],
            'supplier_id' => $this->withExists($required, 'suppliers'),
            'warehouse_id' => $this->withExists($required, 'warehouses'),
            'po_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'document_status' => ['nullable', 'string', 'max:255'],
            'currency_id' => $this->withExists(['nullable'], 'currencies'),
            'exchange_rate' => ['nullable', 'numeric'],
            'order_date' => array_merge($required, ['date']),
            'expected_date' => ['nullable', 'date'],
            'price_list_id' => $this->withExists(['nullable'], 'price_lists'),
            'payment_term_id' => $this->withExists(['nullable'], 'payment_terms'),
            'header_discount_type' => ['nullable', 'string', 'max:255'],
            'header_discount_value' => ['nullable', 'numeric'],
            'header_tax_group_id' => $this->withExists(['nullable'], 'tax_groups'),
            'tax_account_id' => $this->withExists(['nullable'], 'accounts'),
            'discount_account_id' => $this->withExists(['nullable'], 'accounts'),
            'purchase_account_id' => $this->withExists(['nullable'], 'accounts'),
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:1'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => $this->withExists(['nullable'], 'purchase_order_lines'),
            'lines.*._delete' => ['nullable', 'boolean'],
            'lines.*.item_id' => $this->withExists(['required_without:lines.*._delete'], 'items'),
            'lines.*.variant_id' => $this->withExists(['nullable'], 'item_variants'),
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.uom_id' => $this->withExists(['required_without:lines.*._delete'], 'unit_of_measures'),
            'lines.*.ordered_qty' => ['required_without:lines.*._delete', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required_without:lines.*._delete', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', 'string', 'max:255'],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_group_id' => $this->withExists(['nullable'], 'tax_groups'),
            'lines.*.account_id' => $this->withExists(['nullable'], 'accounts'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! Schema::hasTable('items') || ! Schema::hasTable('unit_of_measures')) {
                return;
            }

            $tenantId = (int) $this->input('tenant_id');
            $lines = is_array($this->input('lines')) ? $this->input('lines') : [];

            foreach ($lines as $index => $line) {
                if (! is_array($line) || (bool) ($line['_delete'] ?? false)) {
                    continue;
                }

                $itemId = $line['item_id'] ?? null;
                $uomId = $line['uom_id'] ?? null;
                if (
                    $tenantId > 0
                    && is_numeric($itemId)
                    && is_numeric($uomId)
                    && ! ItemUomOptions::isAllowed($tenantId, (int) $itemId, (int) $uomId, 'purchase')
                ) {
                    $validator->errors()->add(
                        sprintf('lines.%d.uom_id', $index),
                        'The selected UOM is not configured for this item in purchase context.',
                    );
                }
            }
        });
    }

    /**
     * @param list<string> $rules
     * @return list<string>
     */
    private function withExists(array $rules, string $table, string $column = 'id'): array
    {
        $rules[] = 'integer';
        $rules[] = 'min:1';

        if (Schema::hasTable($table)) {
            $rules[] = sprintf('exists:%s,%s', $table, $column);
        }

        return $rules;
    }
}
