<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\EmployeeDocumentStatus;
use Modules\Hr\Enums\EmployeeDocumentType;
final class HrEmployeeDocument extends TenantOwnedModel
{
    use SoftDeletes;
    protected $table = 'hr_employee_documents';
    protected $guarded = ['id'];
    protected function casts(): array { return array_merge(parent::casts(), ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'employee_id' => 'integer', 'document_type' => EmployeeDocumentType::class, 'issued_date' => 'date', 'expiry_date' => 'date', 'status' => EmployeeDocumentStatus::class]); }
    public function employee(): BelongsTo { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
}
