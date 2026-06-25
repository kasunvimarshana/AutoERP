<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\SkillProficiencyLevel;
final class HrEmployeeSkillAssignment extends TenantOwnedModel
{
    protected $table = 'hr_employee_skill_assignments';
    protected $guarded = ['id'];
    protected function casts(): array { return array_merge(parent::casts(), ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'employee_id' => 'integer', 'skill_id' => 'integer', 'proficiency_level' => SkillProficiencyLevel::class, 'years_of_experience' => 'decimal:6', 'is_primary' => 'boolean']); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
    public function skill(): BelongsTo { return $this->belongsTo(HrSkill::class, 'skill_id'); }
}
