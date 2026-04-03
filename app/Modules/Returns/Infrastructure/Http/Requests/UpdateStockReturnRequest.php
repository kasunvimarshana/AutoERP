<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockReturnRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'return_reason' => 'sometimes|nullable|string|max:255',
            'notes'         => 'sometimes|nullable|string',
            'metadata'      => 'nullable|array',
        ];
    }
}
