<?php

declare(strict_types=1);

namespace Modules\Organisation\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Organisation\Domain\Enums\OrganisationStatus;

class UpdateOrganisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = implode(',', array_column(OrganisationStatus::cases(), 'value'));

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', "in:{$statuses}"],
            'meta' => ['nullable', 'array'],
        ];
    }
}
