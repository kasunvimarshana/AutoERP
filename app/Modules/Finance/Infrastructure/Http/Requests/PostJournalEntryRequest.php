<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'posted_by' => ['required', 'integer', 'exists:users,id'],
            'posting_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
