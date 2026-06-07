<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
final class HrEmployeeAvailability extends CoreModel
{
    protected $table = 'hr_employee_availabilities';
    protected $guarded = ['id'];
    protected function casts(): array { return array_merge(parent::casts(), ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'employee_id' => 'integer', 'availability_date' => 'date', 'availability_status' => EmployeeAvailabilityStatus::class, 'source_id' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime']); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
}
