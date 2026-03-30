<?php

declare(strict_types=1);

namespace Modules\Account\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'        => 'sometimes|required|string|max:50',
            'name'        => 'sometimes|required|string|max:255',
            'type'        => 'sometimes|required|string|in:asset,liability,equity,income,expense',
            'subtype'     => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'currency'    => 'nullable|string|size:3',
            'parent_id'   => 'nullable|integer|exists:accounts,id',
            'status'      => 'nullable|string|in:active,inactive',
            'attributes'  => 'nullable|array',
            'metadata'    => 'nullable|array',
        ];
    }
}
