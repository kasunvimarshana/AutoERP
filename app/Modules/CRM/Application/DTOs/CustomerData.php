<?php

declare(strict_types=1);

namespace Modules\CRM\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class CustomerData extends BaseDto
{
    public ?string $id = null;
    public string $code = '';
    public string $name = '';
    public string $type = 'individual';
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $mobile = null;
    public ?string $fax = null;
    public ?string $website = null;
    public ?string $taxNumber = null;
    public ?string $registrationNumber = null;
    public string $currencyCode = 'USD';
    public float $creditLimit = 0.0;
    public float $balance = 0.0;
    public int $paymentTermsDays = 30;
    public string $status = 'active';
    public ?string $billingAddressLine1 = null;
    public ?string $billingAddressLine2 = null;
    public ?string $billingCity = null;
    public ?string $billingState = null;
    public ?string $billingPostalCode = null;
    public ?string $billingCountry = null;
    public ?string $shippingAddressLine1 = null;
    public ?string $shippingAddressLine2 = null;
    public ?string $shippingCity = null;
    public ?string $shippingState = null;
    public ?string $shippingPostalCode = null;
    public ?string $shippingCountry = null;
    public ?string $notes = null;
    public ?array $metadata = null;

    /**
     * Validation rules for creating/updating a customer.
     */
    public function rules(): array
    {
        return [
            'code'                => ['required', 'string', 'max:50'],
            'name'                => ['required', 'string', 'max:200'],
            'type'                => ['sometimes', 'string', 'in:individual,company'],
            'email'               => ['nullable', 'email', 'max:200'],
            'phone'               => ['nullable', 'string', 'max:50'],
            'currency_code'       => ['sometimes', 'string', 'max:10'],
            'credit_limit'        => ['sometimes', 'numeric', 'min:0'],
            'payment_terms_days'  => ['sometimes', 'integer', 'min:0'],
            'status'              => ['sometimes', 'string', 'in:active,inactive,blocked'],
        ];
    }
}
