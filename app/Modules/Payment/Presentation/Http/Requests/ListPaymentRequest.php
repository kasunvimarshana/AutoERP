<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'direction' => ['sometimes', Rule::in(['inbound', 'outbound'])],
            'status' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
