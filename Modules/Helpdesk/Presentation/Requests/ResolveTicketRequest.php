<?php

namespace Modules\Helpdesk\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolver_id'      => ['required', 'uuid'],
            'resolution_notes' => ['nullable', 'string'],
        ];
    }
}
