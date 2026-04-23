<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'total_days' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string',
            'attachment_path' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
