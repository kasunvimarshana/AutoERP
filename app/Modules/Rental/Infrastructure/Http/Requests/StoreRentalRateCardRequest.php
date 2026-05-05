<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'org_unit_id' => ['nullable', 'integer'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'billing_uom' => ['required', 'string', 'in:hourly,daily,weekly,monthly,km,fixed'],
            'rate' => ['required', 'numeric'],
            'asset_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'deposit_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
