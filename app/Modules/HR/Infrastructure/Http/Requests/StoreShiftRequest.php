<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'shift_type' => 'required|string|in:regular,split,flexible,night,rotating',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'break_duration' => 'nullable|integer|min:0',
            'work_days' => 'nullable|array',
            'grace_minutes' => 'nullable|integer|min:0',
            'overtime_threshold' => 'nullable|integer|min:0',
            'is_night_shift' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
