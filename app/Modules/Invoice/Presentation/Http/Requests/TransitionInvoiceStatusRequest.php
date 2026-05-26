<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class TransitionInvoiceStatusRequest extends FormRequest
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
            'target_status' => ['required', 'string', 'in:draft,approved,partially_paid,paid,disputed,cancelled'],
            'expected_row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
