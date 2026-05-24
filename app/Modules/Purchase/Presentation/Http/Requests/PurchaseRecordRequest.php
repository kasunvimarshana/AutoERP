<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Purchase\Domain\Services\PurchaseDomainService;

class PurchaseRecordRequest extends FormRequest
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
        $resource = app(PurchaseDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("purchase.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Purchase resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
