<?php

declare(strict_types=1);

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Generate Invoice Request
 *
 * Validates data for generating an invoice from a job card
 */
class GenerateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job_card_id' => ['required', 'integer', 'exists:job_cards,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'job_card_id' => 'job card',
            'due_date' => 'due date',
            'tax_rate' => 'tax rate',
            'discount_amount' => 'discount amount',
            'payment_terms' => 'payment terms',
        ];
    }
}
