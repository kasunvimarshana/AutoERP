<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Voucher\Domain\Services\VoucherDomainService;

class VoucherRecordRequest extends FormRequest
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
        $resource = app(VoucherDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("voucher.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Voucher resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
