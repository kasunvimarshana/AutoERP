<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:20',
            'shift_type' => 'sometimes|string|in:regular,split,flexible,night,rotating',
            'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string',
            'break_duration' => 'nullable|integer|min:0',
            'work_days' => 'nullable|array',
            'grace_minutes' => 'nullable|integer|min:0',
            'overtime_threshold' => 'nullable|integer|min:0',
            'is_night_shift' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
            'row_version' => 'required|integer|min:1',
        ];
    }
}
