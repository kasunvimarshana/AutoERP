<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests\Concerns;

trait HasExpectedVehicleServiceJobVersion
{
    /** @return array<int, mixed> */
    private function expectedVersionRules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'integer', 'min:1'];
    }

    public function expectedVersion(): ?int
    {
        return $this->filled('expected_version')
            ? (int) $this->input('expected_version')
            : null;
    }
}
