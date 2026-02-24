<?php

namespace Modules\Helpdesk\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKbArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'uuid'],
            'title'       => ['required', 'string', 'max:255'],
            'body'        => ['required', 'string'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
            'visibility'  => ['nullable', 'in:public,agents_only,customers_only'],
        ];
    }
}
