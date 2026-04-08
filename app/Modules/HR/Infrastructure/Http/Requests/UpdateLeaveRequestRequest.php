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
            'leave_type' => 'sometimes|required|string|in:annual,sick,personal,maternity,paternity,unpaid,other',
            'start_date' => 'sometimes|required|date',
            'end_date'   => 'sometimes|required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string',
            'metadata'   => 'nullable|array',
        ];
    }
}
