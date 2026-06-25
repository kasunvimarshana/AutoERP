<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Hr\Enums\EmployeeStatus;
final class HrEmployeeStatusHistory extends CoreModel
{
    protected $table = 'hr_employee_status_histories';
    protected $guarded = ['id'];
    protected function casts(): array { return array_merge(parent::casts(), ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'employee_id' => 'integer', 'old_status' => EmployeeStatus::class, 'new_status' => EmployeeStatus::class, 'changed_by' => 'integer', 'changed_at' => 'datetime']); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
}
