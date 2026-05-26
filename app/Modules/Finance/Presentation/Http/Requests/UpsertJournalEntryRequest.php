<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
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
            'entry_number' => ['required', 'string', 'max:255', ],
            'entry_type' => ['nullable', 'string', 'max:255', ],
            'reference_type' => ['nullable', 'string', 'max:255', ],
            'reference_id' => ['nullable', 'integer', 'min:1', ],
            'description' => ['nullable', 'string', ],
            'entry_date' => ['required', 'date', ],
            'posting_date' => ['nullable', 'date', ],
            'status' => ['nullable', 'string', 'max:255', ],
            'is_reversed' => ['nullable', 'boolean', ],
            'reversal_entry_id' => ['nullable', 'integer', 'min:1', 'exists:journal_entries,id', ],
            'created_by' => ['nullable', 'integer', 'min:1', ],
            'posted_by' => ['nullable', 'integer', 'min:1', ],
            'posted_at' => ['nullable', 'date', ],
        ];
    }
}
