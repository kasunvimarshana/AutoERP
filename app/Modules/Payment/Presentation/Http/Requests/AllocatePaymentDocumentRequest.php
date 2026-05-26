<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class AllocatePaymentDocumentRequest extends FormRequest
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
            'document_type' => ['required', 'string', 'max:255'],
            'document_id' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:255'],
            'allocated_amount' => ['required', 'numeric', 'min:0.0001'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
