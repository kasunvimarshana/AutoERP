<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Payment\Domain\Services\PaymentDomainService;

class PaymentRecordRequest extends FormRequest
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
        $resource = app(PaymentDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("payment.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Payment resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
