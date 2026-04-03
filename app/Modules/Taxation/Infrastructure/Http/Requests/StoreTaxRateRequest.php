<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'          => 'required|integer',
            'name'               => 'required|string|max:255',
            'code'               => 'required|string|max:100',
            'tax_type'           => 'required|string|in:vat,gst,sales_tax,excise,customs,withholding,service_tax,income_tax',
            'calculation_method' => 'required|string|in:inclusive,exclusive,compound',
            'rate'               => 'required|numeric|min:0|max:100',
            'jurisdiction'       => 'nullable|string|max:255',
            'is_active'          => 'boolean',
            'description'        => 'nullable|string',
            'effective_from'     => 'nullable|date',
            'effective_to'       => 'nullable|date',
            'metadata'           => 'nullable|array',
        ];
    }
}
