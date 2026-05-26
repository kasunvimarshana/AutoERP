<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class ReverseJournalEntryRequest extends FormRequest
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
            'expected_row_version' => ['nullable', 'integer', 'min:1'],
            'reversal_entry_id' => ['nullable', 'integer', 'min:1', 'exists:journal_entries,id'],
        ];
    }
}
