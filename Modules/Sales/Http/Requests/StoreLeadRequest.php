<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Lead Request
 *
 * Validates data for creating a new lead.
 */
class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Sales\Entities\Lead::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lead_number' => ['nullable', 'string', 'max:50', 'unique:leads,lead_number'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'source' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
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
