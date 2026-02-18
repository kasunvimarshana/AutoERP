<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Lead Request
 *
 * Validates data for updating an existing lead.
 */
class UpdateLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $this->user()->can('update', $lead);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $leadId = $this->route('lead') ?? $this->route('id');

        return [
            'lead_number' => ['nullable', 'string', 'max:50', "unique:leads,lead_number,{$leadId}"],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'source' => ['nullable', 'string', 'max:100'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contact_name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:new,contacted,qualified,negotiation,won,lost,converted'],
            'stage' => ['nullable', 'in:initial,contacted,qualified,proposal,negotiation,closed'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_revenue' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'tags' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
