<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Domain\Services\VehicleRentalDomainService;

class VehicleRentalRecordRequest extends FormRequest
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
        $resource = app(VehicleRentalDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("vehicle-rental.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Vehicle rental resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
