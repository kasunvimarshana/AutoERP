<?php

namespace Modules\Leave\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reviewer_id' => ['required', 'string'],
            'reason'      => ['nullable', 'string'],
        ];
    }
}
