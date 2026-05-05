<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReturnRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rental_id'         => ['required', 'integer', 'min:1'],
            'return_number'     => ['required', 'string', 'max:100'],
            'returned_at'       => ['required', 'date_format:Y-m-d H:i:s'],
            'end_odometer'      => ['nullable', 'numeric', 'min:0'],
            'actual_days'       => ['nullable', 'numeric', 'min:0'],
            'rental_charge'     => ['required', 'numeric', 'min:0'],
            'extra_charges'     => ['required', 'numeric', 'min:0'],
            'damage_charges'    => ['required', 'numeric', 'min:0'],
            'fuel_charges'      => ['required', 'numeric', 'min:0'],
            'deposit_paid'      => ['required', 'numeric', 'min:0'],
            'refund_amount'     => ['required', 'numeric', 'min:0'],
            'refund_method'     => ['nullable', 'string', 'max:100'],
            'inspection_notes'  => ['nullable', 'string'],
            'notes'             => ['nullable', 'string'],
            'damage_photos'     => ['nullable', 'array'],
            'damage_photos.*'   => ['string'],
            'metadata'          => ['nullable', 'array'],
        ];
    }
}
