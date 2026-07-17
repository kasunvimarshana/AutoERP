<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\Enums\JournalType;

final class StoreJournalEntryRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'journal_date' => ['required', 'date'],
            'journal_type' => ['nullable', Rule::enum(JournalType::class)],
            'journal_number' => ['nullable', 'string', 'max:100'],
            'posting_profile_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('finance_posting_profiles', 'id')],
            'source_module' => ['prohibited'],
            'source_type' => ['prohibited'],
            'source_id' => ['prohibited'],
            'source_number' => ['prohibited'],
            'source_date' => ['prohibited'],
            'description' => ['nullable', 'string'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'min:1'],
            'lines.*.line_number' => ['required', 'integer', 'min:1'],
            'lines.*.debit' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.credit' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.dimension_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('finance_dimensions', 'id')],
            'lines.*.source_line_type' => ['prohibited'],
            'lines.*.source_line_id' => ['prohibited'],
        ];
    }

    public function toData(): CreateJournalEntryData
    {
        return new CreateJournalEntryData(
            tenantId: $this->tenantId(),
            journalDate: (string) $this->input('journal_date'),
            journalType: JournalType::from((string) $this->input('journal_type', JournalType::General->value)),
            organizationUnitId: $this->organizationUnitId(),
            journalNumber: $this->filled('journal_number') ? (string) $this->input('journal_number') : null,
            description: $this->filled('description') ? (string) $this->input('description') : null,
            currencyId: $this->filled('currency_id') ? (int) $this->input('currency_id') : null,
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): JournalLineData => new JournalLineData(
                accountId: (int) $row['account_id'],
                lineNumber: (int) $row['line_number'],
                debit: (string) ($row['debit'] ?? '0.000000'),
                credit: (string) ($row['credit'] ?? '0.000000'),
                description: $row['description'] ?? null,
                dimensionId: isset($row['dimension_id']) ? (int) $row['dimension_id'] : null,
            ), $this->input('lines')),
            postingProfileId: $this->filled('posting_profile_id') ? (int) $this->input('posting_profile_id') : null,
        );
    }
}
