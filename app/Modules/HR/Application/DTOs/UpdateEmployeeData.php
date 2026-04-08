<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial employee updates.
 *
 * All fields are nullable so that absent keys can be distinguished from
 * intentionally-null values. The isProvided() helper tells the service
 * layer whether a field was explicitly included in the incoming payload,
 * enabling safe partial updates without accidentally clearing existing data.
 * The custom toArray() only emits keys that were explicitly supplied, so
 * downstream array_key_exists checks remain valid after serialisation.
 */
class UpdateEmployeeData extends BaseDto
{
    /** @var list<string> Property names that were explicitly present in the source array. */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?string $first_name = null;

    public ?string $last_name = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $date_of_birth = null;

    public ?string $gender = null;

    public ?string $address = null;

    public ?string $employee_number = null;

    public ?string $hire_date = null;

    public ?string $employment_type = null;

    public ?string $status = null;

    public ?int $department_id = null;

    public ?int $position_id = null;

    public ?int $manager_id = null;

    public ?float $salary = null;

    public ?string $currency = null;

    public ?int $org_unit_id = null;

    public ?array $metadata = null;

    public ?bool $is_active = null;

    public ?int $user_id = null;

    /**
     * Track which known property names were present in the source array before
     * delegating to the parent fill logic.
     */
    public function fill(array $data): static
    {
        $known = [
            'id', 'first_name', 'last_name', 'email', 'phone', 'date_of_birth',
            'gender', 'address', 'employee_number', 'hire_date', 'employment_type',
            'status', 'department_id', 'position_id', 'manager_id', 'salary',
            'currency', 'org_unit_id', 'metadata', 'is_active', 'user_id',
        ];
        $this->providedKeys = array_values(array_intersect(array_keys($data), $known));

        return parent::fill($data);
    }

    /**
     * Return only the keys that were explicitly provided in the source data.
     */
    public function toArray(): array
    {
        $all = parent::toArray();

        return array_intersect_key($all, array_flip($this->providedKeys));
    }

    /**
     * Whether a given field was explicitly included in the source payload.
     */
    public function isProvided(string $field): bool
    {
        return in_array($field, $this->providedKeys, true);
    }

    public function rules(): array
    {
        return [
            'first_name'      => 'sometimes|required|string|max:100',
            'last_name'       => 'sometimes|required|string|max:100',
            'email'           => 'sometimes|required|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'date_of_birth'   => 'nullable|date',
            'gender'          => 'nullable|string|in:male,female,other',
            'address'         => 'nullable|string',
            'employee_number' => 'sometimes|required|string|max:50',
            'hire_date'       => 'sometimes|required|date',
            'employment_type' => 'sometimes|required|string|in:full_time,part_time,contract,intern',
            'status'          => 'sometimes|required|string|in:active,inactive,on_leave,terminated',
            'department_id'   => 'nullable|integer',
            'position_id'     => 'nullable|integer',
            'manager_id'      => 'nullable|integer',
            'salary'          => 'nullable|numeric|min:0',
            'currency'        => 'sometimes|required|string|max:3',
            'org_unit_id'     => 'nullable|integer',
            'metadata'        => 'nullable|array',
            'is_active'       => 'boolean',
            'user_id'         => 'nullable|integer',
        ];
    }
}
