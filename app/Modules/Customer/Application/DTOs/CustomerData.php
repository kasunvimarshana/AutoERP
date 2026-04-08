<?php

declare(strict_types=1);

namespace Modules\Customer\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class CustomerData extends BaseDto
{
    public string $type = 'individual';

    public string $name = '';

    public ?string $code = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $tax_number = null;

    public string $currency = 'USD';

    public ?int $payment_terms = null;

    public ?float $credit_limit = null;

    public ?array $address = null;

    public string $status = 'active';

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'type'          => ['string', 'in:individual,business'],
            'name'          => ['required', 'string', 'max:255'],
            'code'          => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'tax_number'    => ['nullable', 'string', 'max:100'],
            'currency'      => ['string', 'size:3'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'credit_limit'  => ['nullable', 'numeric', 'min:0'],
            'address'       => ['nullable', 'array'],
            'status'        => ['string', 'in:active,inactive,blocked'],
            'notes'         => ['nullable', 'string'],
            'metadata'      => ['nullable', 'array'],
        ];
    }
}
