<?php

namespace Modules\Helpdesk\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
