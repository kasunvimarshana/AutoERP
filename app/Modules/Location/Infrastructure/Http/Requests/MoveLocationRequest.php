<?php

declare(strict_types=1);

namespace Modules\Location\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:locations,id',
        ];
    }
}
