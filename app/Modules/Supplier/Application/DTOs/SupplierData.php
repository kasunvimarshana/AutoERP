<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class SupplierData extends BaseDto
{
    public string $name = '';

    public ?string $code = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $tax_number = null;

    public string $currency = 'USD';

    public ?int $payment_terms = null;

    public ?float $credit_limit = null;

    public ?array $address = null;

    public ?array $bank_details = null;

    public string $status = 'active';

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'code'          => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'tax_number'    => ['nullable', 'string', 'max:100'],
            'currency'      => ['string', 'size:3'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'credit_limit'  => ['nullable', 'numeric', 'min:0'],
            'address'       => ['nullable', 'array'],
            'bank_details'  => ['nullable', 'array'],
            'status'        => ['string', 'in:active,inactive,blocked'],
            'notes'         => ['nullable', 'string'],
            'metadata'      => ['nullable', 'array'],
        ];
    }
}
