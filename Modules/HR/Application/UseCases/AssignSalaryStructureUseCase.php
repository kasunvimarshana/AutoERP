<?php

namespace Modules\HR\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Contracts\SalaryStructureAssignmentRepositoryInterface;
use Modules\HR\Domain\Contracts\SalaryStructureRepositoryInterface;
use Modules\HR\Domain\Events\SalaryStructureAssigned;

class AssignSalaryStructureUseCase
{
    public function __construct(
        private SalaryStructureRepositoryInterface           $structureRepo,
        private SalaryStructureAssignmentRepositoryInterface $assignmentRepo,
        private EmployeeRepositoryInterface                  $employeeRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId   = $data['tenant_id'] ?? auth()->user()?->tenant_id;
            $employeeId = $data['employee_id'];
            $structureId = $data['structure_id'];

            $employee = $this->employeeRepo->findById($employeeId);
            if (! $employee) {
                throw new \DomainException("Employee [{$employeeId}] not found.");
            }

            $structure = $this->structureRepo->findById($structureId);
            if (! $structure) {
                throw new \DomainException("Salary structure [{$structureId}] not found.");
            }

            if (! $structure->is_active) {
                throw new \DomainException("Salary structure [{$structureId}] is not active.");
            }

            $baseAmount = bcadd($data['base_amount'] ?? '0.00000000', '0.00000000', 8);

            if (bccomp($baseAmount, '0.00000000', 8) <= 0) {
                throw new \DomainException('Base amount must be greater than zero.');
            }

            $assignment = $this->assignmentRepo->create([
                'tenant_id'      => $tenantId,
                'employee_id'    => $employeeId,
                'structure_id'   => $structureId,
                'base_amount'    => $baseAmount,
                'effective_from' => $data['effective_from'] ?? now()->toDateString(),
            ]);

            Event::dispatch(new SalaryStructureAssigned(
                $assignment->id,
                $tenantId,
                $employeeId,
                $structureId,
            ));

            return $assignment;
        });
    }
}
