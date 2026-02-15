<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\CRM\Models\Lead::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', 'unique:leads,code'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:individual,business'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'company_size' => ['nullable', 'integer', 'min:0'],
            'website' => ['nullable', 'url', 'max:255'],
            'source' => ['required', 'in:website,referral,social_media,email_campaign,cold_call,trade_show,advertisement,partner,other'],
            'source_details' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:new,contacted,qualified,proposal,negotiation,won,lost,unqualified'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'stage' => ['nullable', 'string', 'max:100'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'custom_fields' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Lead title is required',
            'type.required' => 'Lead type is required',
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'source.required' => 'Lead source is required',
            'code.unique' => 'This lead code is already in use',
            'email.email' => 'Please provide a valid email address',
            'website.url' => 'Please provide a valid website URL',
            'expected_close_date.after_or_equal' => 'Expected close date cannot be in the past',
        ];
    }
}
