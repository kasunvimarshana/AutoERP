<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'sometimes|nullable|string|max:255',
            'code'               => 'sometimes|nullable|string|max:100',
            'tax_type'           => 'sometimes|nullable|string|in:vat,gst,sales_tax,excise,customs,withholding,service_tax,income_tax',
            'calculation_method' => 'sometimes|nullable|string|in:inclusive,exclusive,compound',
            'rate'               => 'sometimes|nullable|numeric|min:0|max:100',
            'jurisdiction'       => 'nullable|string|max:255',
            'is_active'          => 'sometimes|nullable|boolean',
            'description'        => 'nullable|string',
            'effective_from'     => 'nullable|date',
            'effective_to'       => 'nullable|date',
            'metadata'           => 'nullable|array',
        ];
    }
}
