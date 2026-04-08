<?php

declare(strict_types=1);

namespace Modules\StockMovement\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockMovementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'   => 'sometimes|nullable|string|in:draft,confirmed,cancelled',
            'notes'    => 'sometimes|nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
