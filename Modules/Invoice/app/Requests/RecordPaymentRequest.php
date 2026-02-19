<?php

declare(strict_types=1);

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Invoice\Enums\PaymentMethod;
use Modules\Invoice\Enums\PaymentStatus;
use Modules\Invoice\Repositories\InvoiceRepository;

/**
 * Record Payment Request
 *
 * Validates data for recording a payment with business rules
 */
class RecordPaymentRequest extends FormRequest
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
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'payment_number' => ['nullable', 'string', 'max:255', 'unique:payments,payment_number'],
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:'.implode(',', PaymentMethod::values())],
            'status' => ['nullable', 'string', 'in:'.implode(',', PaymentStatus::values())],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'processed_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $invoiceId = $this->input('invoice_id');
            $amount = $this->input('amount');

            if ($invoiceId && $amount) {
                $invoiceRepository = app(InvoiceRepository::class);
                $invoice = $invoiceRepository->find($invoiceId);

                if ($invoice && $amount > $invoice->balance) {
                    $validator->errors()->add(
                        'amount',
                        'Payment amount cannot exceed invoice balance of '.$invoice->balance
                    );
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'invoice_id' => 'invoice',
            'payment_number' => 'payment number',
            'payment_date' => 'payment date',
            'payment_method' => 'payment method',
            'reference_number' => 'reference number',
            'processed_by' => 'processed by',
        ];
    }
}
