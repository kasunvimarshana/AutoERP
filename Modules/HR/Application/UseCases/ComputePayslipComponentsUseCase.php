<?php

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Contracts\SalaryStructureAssignmentRepositoryInterface;
use Modules\HR\Domain\Contracts\SalaryStructureRepositoryInterface;
use Modules\HR\Domain\Enums\SalaryComponentType;


class ComputePayslipComponentsUseCase
{
    public function __construct(
        private SalaryStructureAssignmentRepositoryInterface $assignmentRepo,
        private SalaryStructureRepositoryInterface           $structureRepo,
    ) {}

    /**
     * @return array{gross: string, deductions: string, net: string, components: list<array{name: string, code: string, type: string, amount: string}>}
     */
    public function execute(string $tenantId, string $employeeId): array
    {
        $assignment = $this->assignmentRepo->findActiveByEmployee($tenantId, $employeeId);

        if (! $assignment) {
            // No structure assigned — fall back to zero; caller must use employee.salary directly.
            return ['gross' => '0.00000000', 'deductions' => '0.00000000', 'net' => '0.00000000', 'components' => []];
        }

        $structure = $this->structureRepo->findWithLines($assignment->structure_id);

        if (! $structure) {
            return ['gross' => '0.00000000', 'deductions' => '0.00000000', 'net' => '0.00000000', 'components' => []];
        }

        $gross      = '0.00000000';
        $deductions = '0.00000000';
        $components = [];

        foreach (($structure->lines ?? []) as $line) {
            $component = $line->component ?? null;
            if (! $component) {
                continue;
            }

            // Use override_amount if provided on the line, otherwise fall back to component default.
            $amount = $line->override_amount !== null
                ? bcadd($line->override_amount, '0.00000000', 8)
                : bcadd($component->default_amount ?? '0.00000000', '0.00000000', 8);

            $components[] = [
                'name'   => $component->name,
                'code'   => $component->code,
                'type'   => $component->type,
                'amount' => $amount,
            ];

            if ($component->type === SalaryComponentType::Earning->value) {
                $gross = bcadd($gross, $amount, 8);
            } else {
                $deductions = bcadd($deductions, $amount, 8);
            }
        }

        $net = bcsub($gross, $deductions, 8);

        return compact('gross', 'deductions', 'net', 'components');
    }
}
