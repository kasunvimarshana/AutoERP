<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeDriverStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:available,on_trip,suspended,off_duty'],
        ];
    }
}
