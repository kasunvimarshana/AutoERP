<?php

namespace Modules\FieldService\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceTeamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ];
    }
}
