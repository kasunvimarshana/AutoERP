<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Customer\Domain\Constants\CustomerStatus;

final class CustomerStatusTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . implode(',', CustomerStatus::values())],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}