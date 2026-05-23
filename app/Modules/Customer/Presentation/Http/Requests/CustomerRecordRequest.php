<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Customer\Domain\Services\CustomerDomainService;

class CustomerRecordRequest extends FormRequest
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
        $resource = app(CustomerDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("customer.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Customer resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
