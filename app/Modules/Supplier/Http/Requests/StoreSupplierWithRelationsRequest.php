<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\CreateSupplierData;
use Modules\Supplier\Enums\SupplierAddressType;
use Modules\Supplier\Enums\SupplierDocumentStatus;
use Modules\Supplier\Enums\SupplierDocumentType;
use Modules\Supplier\Http\Requests\Concerns\MapsSupplierData;

final class StoreSupplierWithRelationsRequest extends TenantScopedRequest
{
    use MapsSupplierData;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'supplier' => ['required', 'array'],
            ...StoreSupplierRequest::supplierRules('supplier.'),
            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_name' => ['required', 'string', 'max:255'],
            'contacts.*.designation' => ['nullable', 'string', 'max:150'],
            'contacts.*.department' => ['nullable', 'string', 'max:150'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:50'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.is_active' => ['nullable', 'boolean'],
            'contacts.*.notes' => ['nullable', 'string'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.address_type' => ['required', Rule::enum(SupplierAddressType::class)],
            'addresses.*.address_line_1' => ['required', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['nullable', 'string', 'max:120'],
            'addresses.*.state' => ['nullable', 'string', 'max:120'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:40'],
            'addresses.*.country' => ['nullable', 'string', 'max:120'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'addresses.*.is_active' => ['nullable', 'boolean'],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_name' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['required', 'string', 'max:100'],
            'bank_accounts.*.branch_name' => ['nullable', 'string', 'max:255'],
            'bank_accounts.*.swift_code' => ['nullable', 'string', 'max:50'],
            'bank_accounts.*.iban' => ['nullable', 'string', 'max:80'],
            'bank_accounts.*.currency_id' => ['nullable', 'integer', 'min:1'],
            'bank_accounts.*.is_primary' => ['nullable', 'boolean'],
            'bank_accounts.*.is_active' => ['nullable', 'boolean'],
            'bank_accounts.*.notes' => ['nullable', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'min:1'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['required', Rule::enum(SupplierDocumentType::class)],
            'documents.*.document_number' => ['nullable', 'string', 'max:150'],
            'documents.*.issued_date' => ['nullable', 'date'],
            'documents.*.expiry_date' => ['nullable', 'date'],
            'documents.*.status' => ['nullable', Rule::enum(SupplierDocumentStatus::class)],
            'documents.*.notes' => ['nullable', 'string'],
            'item_mappings' => ['nullable', 'array'],
            'item_mappings.*.item_id' => ['required', 'integer', 'min:1'],
            'item_mappings.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'item_mappings.*.supplier_item_code' => ['nullable', 'string', 'max:150'],
            'item_mappings.*.supplier_item_name' => ['nullable', 'string', 'max:255'],
            'item_mappings.*.default_purchase_uom_id' => ['nullable', 'integer', 'min:1'],
            'item_mappings.*.minimum_order_quantity' => ['nullable', 'decimal:0,6', 'gte:0'],
            'item_mappings.*.lead_time_days' => ['nullable', 'integer', 'min:0'],
            'item_mappings.*.is_preferred' => ['nullable', 'boolean'],
            'item_mappings.*.is_active' => ['nullable', 'boolean'],
            'credit_profile' => ['nullable', 'array'],
            'credit_profile.credit_limit' => ['nullable', 'decimal:0,6', 'gte:0'],
            'credit_profile.credit_period_days' => ['nullable', 'integer', 'min:0'],
            'credit_profile.warning_threshold_percent' => ['nullable', 'decimal:0,6', 'between:0,100'],
            'credit_profile.allow_over_credit' => ['nullable', 'boolean'],
            'credit_profile.allow_partial_payment' => ['nullable', 'boolean'],
            'credit_profile.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): CreateSupplierData
    {
        $validated = $this->validated();

        return $this->mapSupplierData((array) $validated['supplier'], $validated);
    }
}
