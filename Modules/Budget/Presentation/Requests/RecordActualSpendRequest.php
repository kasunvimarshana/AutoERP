<?php

namespace Modules\Budget\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordActualSpendRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
