<?php

declare(strict_types=1);

namespace Modules\Settings\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'     => 'required|integer',
            'group_key'     => 'required|string|max:100',
            'setting_key'   => 'required|string|max:100',
            'label'         => 'required|string|max:255',
            'value'         => 'nullable',
            'default_value' => 'nullable',
            'setting_type'  => 'required|string|in:string,integer,float,boolean,json,array',
            'description'   => 'nullable|string',
            'is_system'     => 'boolean',
            'is_editable'   => 'boolean',
            'metadata'      => 'nullable|array',
        ];
    }
}
