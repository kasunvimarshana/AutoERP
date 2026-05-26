<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class RecalculateInvoiceTotalsRequest extends FormRequest
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
            'line_tax_rates' => ['nullable', 'array'],
            'line_tax_rates.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'header_tax_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
