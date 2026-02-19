<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Accounting\Enums\JournalEntryStatus;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Accounting\Models\JournalEntry::class);
    }

    public function rules(): array
    {
        return [
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'entry_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('journal_entries', 'entry_number')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'entry_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::enum(JournalEntryStatus::class)],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => [
                'required',
                Rule::exists('accounts', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
            'lines.*.metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);

            $totalDebits = array_sum(array_column($lines, 'debit'));
            $totalCredits = array_sum(array_column($lines, 'credit'));

            if (bccomp((string) $totalDebits, (string) $totalCredits, 6) !== 0) {
                $validator->errors()->add('lines', 'Total debits must equal total credits');
            }

            foreach ($lines as $index => $line) {
                if (bccomp((string) $line['debit'], '0', 6) > 0 && bccomp((string) $line['credit'], '0', 6) > 0) {
                    $validator->errors()->add("lines.{$index}", 'A line cannot have both debit and credit amounts');
                }

                if (bccomp((string) $line['debit'], '0', 6) === 0 && bccomp((string) $line['credit'], '0', 6) === 0) {
                    $validator->errors()->add("lines.{$index}", 'A line must have either debit or credit amount');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
            'entry_number' => 'entry number',
            'entry_date' => 'entry date',
            'reference' => 'reference',
            'description' => 'description',
            'status' => 'status',
            'lines.*.account_id' => 'account',
            'lines.*.debit' => 'debit',
            'lines.*.credit' => 'credit',
        ];
    }
}
