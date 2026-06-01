<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSalesReturnLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [

        ];
    }
}
