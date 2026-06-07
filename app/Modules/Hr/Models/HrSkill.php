<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class HrSkill extends HrMasterModel
{
    protected $table = 'hr_skills';
    public function assignments(): HasMany { return $this->hasMany(HrEmployeeSkillAssignment::class, 'skill_id'); }
    public function employees(): BelongsToMany { return $this->belongsToMany(HrEmployee::class, 'hr_employee_skill_assignments', 'skill_id', 'employee_id')->withPivot(['id', 'proficiency_level', 'years_of_experience', 'is_primary'])->withTimestamps(); }
}
