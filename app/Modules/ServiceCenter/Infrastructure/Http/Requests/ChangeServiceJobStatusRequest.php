<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeServiceJobStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
        ];
    }
}
