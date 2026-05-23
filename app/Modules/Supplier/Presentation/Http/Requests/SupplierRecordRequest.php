<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Supplier\Domain\Services\SupplierDomainService;

class SupplierRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $resource = app(SupplierDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("supplier.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Supplier resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
