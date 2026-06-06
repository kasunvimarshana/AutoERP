<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\CreateSupplierData;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Enums\SupplierType;
use Modules\Supplier\Http\Requests\Concerns\MapsSupplierData;

final class StoreSupplierRequest extends TenantScopedRequest
{
    use MapsSupplierData;

    public function rules(): array
    {
        return self::supplierRules();
    }

    public function toData(): CreateSupplierData
    {
        return $this->mapSupplierData($this->validated());
    }

    public static function supplierRules(string $prefix = ''): array
    {
        $key = fn (string $name): string => $prefix.$name;

        return [
            $key('tenant_id') => $prefix === '' ? ['required', 'integer', 'min:1'] : ['prohibited'],
            $key('organization_unit_id') => $prefix === '' ? ['nullable', 'integer', 'min:1'] : ['prohibited'],
            $key('supplier_number') => ['nullable', 'string', 'max:80'],
            $key('code') => ['required', 'string', 'max:80'],
            $key('name') => ['required', 'string', 'max:255'],
            $key('supplier_type') => ['required', Rule::enum(SupplierType::class)],
            $key('status') => ['nullable', Rule::enum(SupplierStatus::class)],
            $key('legal_name') => ['nullable', 'string', 'max:255'],
            $key('display_name') => ['nullable', 'string', 'max:255'],
            $key('email') => ['nullable', 'email', 'max:255'],
            $key('phone') => ['nullable', 'string', 'max:50'],
            $key('mobile') => ['nullable', 'string', 'max:50'],
            $key('website') => ['nullable', 'url', 'max:255'],
            $key('default_currency_id') => ['nullable', 'integer', 'min:1'],
            $key('payment_term_id') => ['nullable', 'integer', 'min:1'],
            $key('tax_registration_number') => ['nullable', 'string', 'max:100'],
            $key('vat_number') => ['nullable', 'string', 'max:100'],
            $key('svat_number') => ['nullable', 'string', 'max:100'],
            $key('business_registration_number') => ['nullable', 'string', 'max:100'],
            $key('credit_limit') => ['nullable', 'decimal:0,6', 'gte:0'],
            $key('opening_balance') => ['prohibited'],
            $key('is_credit_allowed') => ['nullable', 'boolean'],
            $key('is_advance_allowed') => ['nullable', 'boolean'],
            $key('notes') => ['nullable', 'string'],
            $key('metadata') => ['nullable', 'array'],
        ];
    }
}
