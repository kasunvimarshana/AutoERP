<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class PayrollData extends BaseDto
{
    public int $tenant_id;

    public int $employee_id;

    public string $pay_period_start;

    public string $pay_period_end;

    public float $gross_salary;

    public float $net_salary;

    public float $deductions = 0.0;

    public float $allowances = 0.0;

    public float $bonuses = 0.0;

    public string $currency = 'USD';

    public string $status = 'draft';

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenant_id'        => 'required|integer',
            'employee_id'      => 'required|integer',
            'pay_period_start' => 'required|date_format:Y-m-d',
            'pay_period_end'   => 'required|date_format:Y-m-d',
            'gross_salary'     => 'required|numeric|min:0',
            'net_salary'       => 'required|numeric|min:0',
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
