<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Domain\Services\FinanceIntegrityService;

class FinanceRecordRequest extends FormRequest
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
        $resource = app(FinanceIntegrityService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("finance.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Finance resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
