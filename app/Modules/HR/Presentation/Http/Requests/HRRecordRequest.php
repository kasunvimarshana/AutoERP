<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\HR\Domain\Services\HRDomainService;

class HRRecordRequest extends FormRequest
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
        $resource = app(HRDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("hr.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["HR resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
