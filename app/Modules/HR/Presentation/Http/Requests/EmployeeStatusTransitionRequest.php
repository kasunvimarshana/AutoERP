<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HR\Domain\Constants\EmployeeStatus;

final class EmployeeStatusTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employment_status' => ['required', 'string', 'in:' . implode(',', EmployeeStatus::values())],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
