<?php

declare(strict_types=1);

namespace Modules\Crm\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:call,email,meeting,note,task'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contact_id' => ['nullable', 'integer'],
            'lead_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}
