<?php

namespace Modules\Contracts\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TerminateContractRequest extends FormRequest
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
