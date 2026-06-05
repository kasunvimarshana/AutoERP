<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
