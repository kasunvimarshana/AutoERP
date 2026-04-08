<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryCycleCountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'zone_id'      => 'sometimes|nullable|integer',
            'location_id'  => 'sometimes|nullable|integer',
            'count_method' => 'sometimes|string|in:abc,frequency,random,manual',
            'assigned_to'  => 'sometimes|nullable|integer',
            'scheduled_at' => 'sometimes|nullable|date',
            'notes'        => 'sometimes|nullable|string',
            'metadata'     => 'nullable|array',
        ];
    }
}
