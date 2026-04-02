<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'        => 'required|integer',
            'title'            => 'required|string|max:255',
            'start_date'       => 'required|date_format:Y-m-d',
            'description'      => 'nullable|string',
            'trainer'          => 'nullable|string|max:255',
            'location'         => 'nullable|string|max:255',
            'end_date'         => 'nullable|date_format:Y-m-d',
            'max_participants' => 'nullable|integer|min:1',
            'status'           => 'nullable|string|in:scheduled,in_progress,completed,cancelled',
            'metadata'         => 'nullable|array',
            'is_active'        => 'nullable|boolean',
        ];
    }
}
