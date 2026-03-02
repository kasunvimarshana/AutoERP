<?php

declare(strict_types=1);

namespace Modules\Pos\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Pos\Domain\Enums\PosPaymentMethod;

class RefundPosOrderRequest extends FormRequest
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
            'refund_amount' => ['required', 'numeric', 'min:0.0001'],
            'method' => ['required', 'string', 'in:'.$methods],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
