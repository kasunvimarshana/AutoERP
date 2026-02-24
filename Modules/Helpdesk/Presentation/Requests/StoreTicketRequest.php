<?php

namespace Modules\Helpdesk\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'uuid'],
            'subject'     => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority'    => ['nullable', 'in:low,medium,high,critical'],
            'sla_due_at'  => ['nullable', 'date'],
        ];
    }
}
