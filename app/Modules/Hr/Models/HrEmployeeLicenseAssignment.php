<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\EmployeeDocumentStatus;
final class HrEmployeeLicenseAssignment extends TenantOwnedModel
{
    protected $table = 'hr_employee_license_assignments';
    protected $guarded = ['id'];
    protected function casts(): array { return array_merge(parent::casts(), ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'employee_id' => 'integer', 'license_id' => 'integer', 'issued_date' => 'date', 'expiry_date' => 'date', 'status' => EmployeeDocumentStatus::class]); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
    public function license(): BelongsTo { return $this->belongsTo(HrLicense::class, 'license_id'); }
}
