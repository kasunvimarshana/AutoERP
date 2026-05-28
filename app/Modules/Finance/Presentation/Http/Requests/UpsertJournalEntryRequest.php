<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Finance\Domain\Constants\JournalEntryStatus;

final class UpsertJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'fiscal_period_id' => ['nullable', 'integer', 'min:1', 'exists:fiscal_periods,id', ],
            'entry_number' => array_merge($required, ['string', 'max:255']),
            'entry_type' => ['nullable', 'string', 'in:MANUAL,AUTO,SYSTEM,OPENING,CLOSING,ADJUSTMENT'],
            'reference_type' => ['nullable', 'string', 'max:255', ],
            'reference_id' => ['nullable', 'integer', 'min:1', ],
            'description' => ['nullable', 'string', ],
            'entry_date' => array_merge($required, ['date']),
            'posting_date' => ['nullable', 'date', ],
            'status' => ['nullable', 'string', 'in:' . implode(',', JournalEntryStatus::values())],
            'is_reversed' => ['nullable', 'boolean'],
            'reversal_entry_id' => ['nullable', 'integer', 'min:1', 'exists:journal_entries,id', ],
            'created_by' => ['nullable', 'integer', 'min:1', ],
            'posted_by' => ['nullable', 'integer', 'min:1', ],
            'posted_at' => ['nullable', 'date', ],
            'lines' => ['nullable', 'array', 'min:2'],
            'lines.*.organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'lines.*.metadata' => ['nullable', 'array'],
            'lines.*.account_id' => ['required_with:lines', 'integer', 'min:1', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.debit_amount' => ['nullable', 'numeric', 'gt:0', 'required_without:lines.*.credit_amount'],
            'lines.*.credit_amount' => ['nullable', 'numeric', 'gt:0', 'required_without:lines.*.debit_amount'],
            'lines.*.currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'lines.*.exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.base_debit_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.base_credit_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'min:1', 'exists:cost_centers,id'],
            'lines.*.tax_rate_id' => ['nullable', 'integer', 'min:1', 'exists:tax_rates,id'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.line_number' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
