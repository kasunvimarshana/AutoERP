<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PostJournalEntryRequest extends FormRequest
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
        return [
            'expected_row_version' => ['nullable', 'integer', 'min:1'],
            'posting_date' => ['nullable', 'date'],
            'posted_by' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
