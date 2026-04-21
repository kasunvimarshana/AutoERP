<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveSalesReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
