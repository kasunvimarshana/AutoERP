<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateJournalEntryFromEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'event_type' => ['required', 'string', 'max:120'],
            'idempotency_key' => ['required', 'string', 'max:190'],
            'entry_number' => ['required', 'string', 'max:120'],
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'fiscal_period_id' => ['nullable', 'integer', 'min:1'],
            'reference_type' => ['nullable', 'string', 'max:120'],
            'reference_id' => ['nullable'],
            'source_module' => ['nullable', 'string', 'max:120'],
            'actor_user_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.debit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.currency_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.base_debit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.base_credit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.tax_rate_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.metadata' => ['nullable', 'array'],
        ];
    }
}
