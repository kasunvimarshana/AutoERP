<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class EmployeeDocumentModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_employee_documents';

    protected $fillable = ['tenant_id', 'org_unit_id', 'row_version', 'employee_id', 'document_type', 'title', 'description', 'file_path', 'mime_type', 'file_size', 'issued_date', 'expiry_date', 'metadata'];

    protected $casts = ['tenant_id' => 'integer', 'org_unit_id' => 'integer', 'row_version' => 'integer', 'employee_id' => 'integer', 'document_type' => 'string', 'file_size' => 'integer', 'issued_date' => 'date', 'expiry_date' => 'date', 'metadata' => 'array'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }
}
