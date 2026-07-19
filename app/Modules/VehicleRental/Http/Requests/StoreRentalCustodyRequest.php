<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalCustodyData;
use Modules\VehicleRental\Enums\RentalCustodyEventType;

final class StoreRentalCustodyRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'event_type' => ['required', Rule::in([RentalCustodyEventType::Handover->value, RentalCustodyEventType::Return->value])],
            'event_at' => ['required', 'date'],
            'odometer' => ['required', 'numeric', 'min:0'],
            'fuel_level' => ['nullable', 'string', 'max:50'],
            'condition_notes' => ['nullable', 'string'],
            'damage_notes' => ['nullable', 'string'],
            'expected_version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toData(): RentalCustodyData
    {
        return new RentalCustodyData(
            eventType: RentalCustodyEventType::from((string) $this->validated('event_type')),
            eventAt: (string) $this->validated('event_at'),
            odometer: (string) $this->validated('odometer'),
            fuelLevel: $this->nullableString('fuel_level'),
            conditionNotes: $this->nullableString('condition_notes'),
            damageNotes: $this->nullableString('damage_notes'),
            actorId: $this->currentUserId(),
        );
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('expected_version');
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
