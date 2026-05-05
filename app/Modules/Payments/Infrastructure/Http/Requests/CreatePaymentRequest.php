<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Payments\Domain\ValueObjects\PaymentMethod;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $methods = implode(',', array_map(static fn (PaymentMethod $method): string => $method->value, PaymentMethod::cases()));

        return [
            'org_unit_id' => ['nullable', 'uuid'],
            'payment_number' => ['required', 'string', 'max:64'],
            'invoice_id' => ['required', 'uuid'],
            'payment_method' => ['required', 'string', "in:{$methods}"],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'reference_number' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
