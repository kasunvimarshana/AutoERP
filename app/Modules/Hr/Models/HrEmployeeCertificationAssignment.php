<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Hr\Enums\EmployeeDocumentStatus;
final class HrEmployeeCertificationAssignment extends CoreModel
{
    protected $table = 'hr_employee_certification_assignments';
    protected $guarded = ['id'];
    protected function casts(): array { return array_merge(parent::casts(), ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'employee_id' => 'integer', 'certification_id' => 'integer', 'issued_date' => 'date', 'expiry_date' => 'date', 'status' => EmployeeDocumentStatus::class]); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
    public function certification(): BelongsTo { return $this->belongsTo(HrCertification::class, 'certification_id'); }
}
