<?php

namespace Modules\Communication\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChannelRequest extends FormRequest
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
            'type'        => ['required', 'string', 'in:direct,group,channel'],
        ];
    }
}
