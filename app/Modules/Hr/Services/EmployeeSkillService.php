<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\DTOs\EmployeeSkillAssignmentData;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeSkillAssignment;
use Modules\Hr\Models\HrSkill;
final class EmployeeSkillService
{
    public function __construct(private readonly DecimalMath $math, private readonly EmployeeValidationService $validator) {}
    public function create(HrEmployee $employee, EmployeeSkillAssignmentData $data): HrEmployeeSkillAssignment { $skill = HrSkill::query()->findOrFail($data->skillId); $this->validator->assertScopedActive($skill, $employee, 'skill'); if ($employee->skillAssignments()->where('skill_id', $data->skillId)->exists()) { throw new InvalidArgumentException('Skill is already assigned to the employee.'); } if ($this->math->isNegative($data->yearsOfExperience)) { throw new InvalidArgumentException('Years of experience cannot be negative.'); } return $employee->skillAssignments()->create($this->attributes($employee, $data)); }
    public function update(HrEmployee $employee, HrEmployeeSkillAssignment $row, EmployeeSkillAssignmentData $data): HrEmployeeSkillAssignment { $this->owned($employee, $row); $skill = HrSkill::query()->findOrFail($data->skillId); $this->validator->assertScopedActive($skill, $employee, 'skill'); if ($employee->skillAssignments()->whereKeyNot($row->getKey())->where('skill_id', $data->skillId)->exists()) { throw new InvalidArgumentException('Skill is already assigned to the employee.'); } if ($this->math->isNegative($data->yearsOfExperience)) { throw new InvalidArgumentException('Years of experience cannot be negative.'); } $row->fill($this->attributes($employee, $data, false))->save(); return $row->refresh()->load('skill'); }
    public function delete(HrEmployee $employee, HrEmployeeSkillAssignment $row): void { $this->owned($employee, $row); $row->delete(); }
    public function replace(HrEmployee $employee, array $rows): void { $employee->skillAssignments()->delete(); foreach ($rows as $row) { $this->create($employee, $row); } }
    private function attributes(HrEmployee $employee, EmployeeSkillAssignmentData $data, bool $scope = true): array { return [...($scope ? ['tenant_id' => $employee->tenant_id, 'organization_unit_id' => $employee->organization_unit_id] : []), 'skill_id' => $data->skillId, 'proficiency_level' => $data->proficiencyLevel, 'years_of_experience' => $this->math->normalize($data->yearsOfExperience), 'is_primary' => $data->isPrimary]; }
    private function owned(HrEmployee $employee, HrEmployeeSkillAssignment $row): void { if ((int) $row->employee_id !== (int) $employee->getKey()) { throw new InvalidArgumentException('Employee skill does not belong to the employee.'); } }
}
