<?php

declare(strict_types=1);

namespace Modules\Sequence\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sequenceId = $this->route('sequence');

        $compositeUnique = Rule::unique('sequences')
            ->ignore(is_object($sequenceId) ? $sequenceId->id : $sequenceId)
            ->where(fn ($query) => $query
                ->where('tenant_id', $this->input('tenant_id'))
                ->where('document_type', $this->input('document_type'))
                ->where('period_value', $this->input('period_value'))
                ->when(
                    $this->filled('organization_unit_id'),
                    fn ($inner) => $inner->where('organization_unit_id', $this->input('organization_unit_id')),
                    fn ($inner) => $inner->whereNull('organization_unit_id'),
                ));

        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'document_type' => ['required', 'string', 'max:255', $compositeUnique],
            'prefix' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'padding' => ['nullable', 'integer', 'min:1'],
            'next_number' => ['nullable', 'integer', 'min:1'],
            'period_type' => ['nullable', 'string', 'in:yearly,monthly,infinite'],
            'period_value' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
