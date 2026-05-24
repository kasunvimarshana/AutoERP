<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\VehicleService\Domain\Services\VehicleServiceDomainService;

class VehicleServiceRecordRequest extends FormRequest
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
        $resource = app(VehicleServiceDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("vehicle-service.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Vehicle service resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
