<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Enums\PreferredCommunicationChannel;
use Modules\Customer\Http\Requests\Concerns\MapsCustomerData;

final class StoreCustomerRequest extends TenantScopedRequest
{
    use MapsCustomerData;

    public function rules(): array
    {
        return self::customerRules();
    }

    public function toData(): CreateCustomerData
    {
        return $this->mapCustomerData($this->validated());
    }

    public static function customerRules(string $prefix = ''): array
    {
        $key = fn (string $name): string => $prefix.$name;

        return [
            $key('tenant_id') => $prefix === '' ? ['required', 'integer', 'min:1'] : ['prohibited'],
            $key('organization_unit_id') => $prefix === '' ? ['nullable', 'integer', 'min:1'] : ['prohibited'],
            $key('customer_number') => ['nullable', 'string', 'max:80'],
            $key('code') => ['nullable', 'string', 'max:80'],
            $key('name') => ['required', 'string', 'max:255'],
            $key('customer_type') => ['required', Rule::enum(CustomerType::class)],
            $key('status') => ['nullable', Rule::enum(CustomerStatus::class)],
            $key('legal_name') => ['nullable', 'string', 'max:255'],
            $key('display_name') => ['nullable', 'string', 'max:255'],
            $key('email') => ['nullable', 'email', 'max:255'],
            $key('phone') => ['nullable', 'string', 'max:50', 'regex:/^[0-9+().\s-]{5,50}$/'],
            $key('mobile') => ['nullable', 'string', 'max:50', 'regex:/^[0-9+().\s-]{5,50}$/'],
            $key('website') => ['nullable', 'url', 'max:255'],
            $key('default_currency_id') => ['nullable', 'integer', 'min:1'],
            $key('payment_term_id') => ['nullable', 'integer', 'min:1'],
            $key('tax_registration_number') => ['nullable', 'string', 'max:100'],
            $key('vat_number') => ['nullable', 'string', 'max:100'],
            $key('svat_number') => ['nullable', 'string', 'max:100'],
            $key('business_registration_number') => ['nullable', 'string', 'max:100'],
            $key('credit_limit') => ['prohibited'],
            $key('opening_balance') => ['prohibited'],
            $key('is_credit_allowed') => ['prohibited'],
            $key('is_advance_allowed') => ['prohibited'],
            $key('is_tax_exempt') => ['nullable', 'boolean'],
            $key('marketing_consent') => ['nullable', 'boolean'],
            $key('preferred_communication_channel') => ['nullable', Rule::enum(PreferredCommunicationChannel::class)],
            $key('notes') => ['nullable', 'string'],
            $key('metadata') => ['nullable', 'array'],
        ];
    }
}
