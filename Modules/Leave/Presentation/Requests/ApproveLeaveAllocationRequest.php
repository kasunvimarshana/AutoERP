<?php

namespace Modules\Leave\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approver_id' => ['required', 'uuid'],
        ];
    }
}
