<?php

declare(strict_types=1);

namespace Modules\Organisation\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Organisation\Domain\Enums\OrganisationNodeType;

class CreateOrganisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = implode(',', array_column(OrganisationNodeType::cases(), 'value'));

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', "in:{$types}"],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
