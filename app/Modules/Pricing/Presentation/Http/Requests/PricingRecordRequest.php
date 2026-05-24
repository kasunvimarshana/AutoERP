<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Pricing\Domain\Services\PricingDomainService;

class PricingRecordRequest extends FormRequest
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
        $resource = app(PricingDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("pricing.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Pricing resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
