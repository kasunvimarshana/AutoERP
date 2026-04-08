<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial payroll record updates.
 *
 * All fields are nullable so that absent keys can be distinguished from
 * intentionally-null values. The isProvided() helper tells the service
 * layer whether a field was explicitly included in the incoming payload.
 */
class UpdatePayrollData extends BaseDto
{
    /** @var list<string> Property names that were explicitly present in the source array. */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?int $tenant_id = null;

    public ?int $employee_id = null;

    public ?string $pay_period_start = null;

    public ?string $pay_period_end = null;

    public ?float $gross_salary = null;

    public ?float $net_salary = null;

    public ?float $deductions = null;

    public ?float $allowances = null;

    public ?float $bonuses = null;

    public ?string $currency = null;

    public ?string $status = null;

    public ?string $notes = null;

    public ?array $metadata = null;

    public function fill(array $data): static
    {
        $known = [
            'id', 'tenant_id', 'employee_id', 'pay_period_start', 'pay_period_end',
            'gross_salary', 'net_salary', 'deductions', 'allowances', 'bonuses',
            'currency', 'status', 'notes', 'metadata',
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
            'pay_period_start' => 'sometimes|required|date_format:Y-m-d',
            'pay_period_end'   => 'sometimes|required|date_format:Y-m-d',
            'gross_salary'     => 'sometimes|required|numeric|min:0',
            'net_salary'       => 'sometimes|required|numeric|min:0',
            'deductions'       => 'nullable|numeric|min:0',
            'allowances'       => 'nullable|numeric|min:0',
            'bonuses'          => 'nullable|numeric|min:0',
            'currency'         => 'nullable|string|max:3',
            'status'           => 'nullable|string|in:draft,processed,paid',
            'notes'            => 'nullable|string',
            'metadata'         => 'nullable|array',
        ];
    }
}
