<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class UnallocatePaymentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'allocation_id' => ['nullable', 'integer', 'min:1', 'exists:payment_allocations,id'],
            'document_type' => ['nullable', 'string', 'max:255'],
            'document_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
