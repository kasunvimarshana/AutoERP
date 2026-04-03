<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryCycleCountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'       => 'required|integer',
            'reference_number'=> 'required|string|max:100',
            'warehouse_id'    => 'required|integer',
            'zone_id'         => 'nullable|integer',
            'location_id'     => 'nullable|integer',
            'count_method'    => 'string|in:abc,frequency,random,manual',
            'assigned_to'     => 'nullable|integer',
            'scheduled_at'    => 'nullable|date',
            'notes'           => 'nullable|string',
            'metadata'        => 'nullable|array',
        ];
    }
}
