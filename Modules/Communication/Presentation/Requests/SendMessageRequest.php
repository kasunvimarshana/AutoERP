<?php

namespace Modules\Communication\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'type' => ['in:text,file,image'],
        ];
    }
}
