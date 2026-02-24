<?php

namespace Modules\Helpdesk\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateKbArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'helpful' => ['required', 'boolean'],
        ];
    }
}
