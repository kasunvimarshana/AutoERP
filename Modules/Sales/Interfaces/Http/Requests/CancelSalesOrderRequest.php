<?php

declare(strict_types=1);

namespace Modules\Sales\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelSalesOrderRequest extends FormRequest
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
