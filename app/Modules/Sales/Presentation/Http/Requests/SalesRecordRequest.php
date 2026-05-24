<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Sales\Domain\Services\SalesDomainService;

class SalesRecordRequest extends FormRequest
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
        $resource = app(SalesDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("sales.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Sales resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
