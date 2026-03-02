<?php

declare(strict_types=1);

namespace Modules\Pos\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Pos\Domain\Enums\PosPaymentMethod;

class PayPosOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $methods = implode(',', array_column(PosPaymentMethod::cases(), 'value'));

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string', 'in:'.$methods],
            'payments.*.amount' => ['required', 'numeric', 'min:0.0001'],
            'payments.*.currency' => ['nullable', 'string', 'size:3'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
