<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
