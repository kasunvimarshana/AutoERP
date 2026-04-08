<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkEmployeeToUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['user_id' => 'required|integer'];
    }
}
