<?php
declare(strict_types=1);
namespace Modules\Hr\DTOs;
use Modules\Hr\Enums\SkillProficiencyLevel;
final readonly class EmployeeSkillAssignmentData { public function __construct(public int $skillId, public SkillProficiencyLevel $proficiencyLevel = SkillProficiencyLevel::Beginner, public string $yearsOfExperience = '0.000000', public bool $isPrimary = false) {} }
