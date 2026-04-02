<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial attendance updates.
 */
class UpdateAttendanceData extends BaseDto
{
    /** @var list<string> */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?int $tenant_id = null;

    public ?int $employee_id = null;

    public ?string $date = null;

    public ?string $check_in_time = null;

    public ?string $status = null;

    public ?string $notes = null;

    public ?float $hours_worked = null;

    public ?string $check_out_time = null;

    public function fill(array $data): static
    {
        $known = [
            'id', 'tenant_id', 'employee_id', 'date', 'check_in_time',
            'status', 'notes', 'hours_worked', 'check_out_time',
        ];
        $this->providedKeys = array_values(array_intersect(array_keys($data), $known));

        return parent::fill($data);
    }

    public function toArray(): array
    {
        $all = parent::toArray();

        return array_intersect_key($all, array_flip($this->providedKeys));
    }

    public function isProvided(string $field): bool
    {
        return in_array($field, $this->providedKeys, true);
    }

    public function rules(): array
    {
        return [
            'date'           => 'sometimes|required|date_format:Y-m-d',
            'check_in_time'  => 'sometimes|required|date_format:H:i:s',
            'status'         => 'sometimes|required|string|in:present,absent,late,half_day',
            'notes'          => 'nullable|string',
            'hours_worked'   => 'nullable|numeric|min:0',
            'check_out_time' => 'nullable|date_format:H:i:s',
        ];
    }
}
