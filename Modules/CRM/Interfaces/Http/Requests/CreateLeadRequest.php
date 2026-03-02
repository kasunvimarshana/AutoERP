<?php

declare(strict_types=1);

namespace Modules\Crm\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contact_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:new,contacted,qualified,proposal,negotiation,won,lost'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3', 'in:'.implode(',', config('currency.supported', ['LKR']))],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
