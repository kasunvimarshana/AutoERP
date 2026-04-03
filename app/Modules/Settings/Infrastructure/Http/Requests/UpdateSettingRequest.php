<?php

declare(strict_types=1);

namespace Modules\Settings\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_key'     => 'sometimes|required|string|max:100',
            'setting_key'   => 'sometimes|required|string|max:100',
            'label'         => 'sometimes|required|string|max:255',
            'value'         => 'nullable',
            'default_value' => 'nullable',
            'setting_type'  => 'sometimes|required|string|in:string,integer,float,boolean,json,array',
            'description'   => 'nullable|string',
            'is_system'     => 'nullable|boolean',
            'is_editable'   => 'nullable|boolean',
            'metadata'      => 'nullable|array',
        ];
    }
}
