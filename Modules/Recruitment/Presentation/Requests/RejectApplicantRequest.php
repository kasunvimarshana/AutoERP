<?php

namespace Modules\Recruitment\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reviewer_id'      => ['required', 'uuid'],
            'rejection_reason' => ['nullable', 'string'],
        ];
    }
}
