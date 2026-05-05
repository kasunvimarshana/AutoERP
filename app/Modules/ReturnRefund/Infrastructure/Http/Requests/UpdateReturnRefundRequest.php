<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReturnRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_odometer'     => ['nullable', 'numeric', 'min:0'],
            'actual_days'      => ['nullable', 'numeric', 'min:0'],
            'rental_charge'    => ['nullable', 'numeric', 'min:0'],
            'extra_charges'    => ['nullable', 'numeric', 'min:0'],
            'damage_charges'   => ['nullable', 'numeric', 'min:0'],
            'fuel_charges'     => ['nullable', 'numeric', 'min:0'],
            'deposit_paid'     => ['nullable', 'numeric', 'min:0'],
            'refund_amount'    => ['nullable', 'numeric', 'min:0'],
            'refund_method'    => ['nullable', 'string', 'max:100'],
            'inspection_notes' => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
            'damage_photos'    => ['nullable', 'array'],
            'damage_photos.*'  => ['string'],
            'metadata'         => ['nullable', 'array'],
        ];
    }
}
