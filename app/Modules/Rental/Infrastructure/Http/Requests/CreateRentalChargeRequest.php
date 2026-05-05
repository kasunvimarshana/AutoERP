<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRentalChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'charge_type' => ['required', 'string', 'in:fuel,damage,overtime,toll,other'],
            'description' => ['required', 'string', 'max:255'],
            'quantity'    => ['required', 'numeric', 'min:0.0001'],
            'unit_price'  => ['required', 'numeric', 'min:0'],
        ];
    }
}
