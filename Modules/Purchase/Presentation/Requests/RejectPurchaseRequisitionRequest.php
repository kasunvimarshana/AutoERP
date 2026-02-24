<?php

namespace Modules\Purchase\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectPurchaseRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
