<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class OrganizationUnitAttachmentModel extends BaseModel
{

    use HasTenant;
    use HasAudit;
    use SoftDeletes;

    protected $table = 'org_unit_attachments';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'uuid',
        'name',
        'file_path',
        'mime_type',
        'size',
        'type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
    ];

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'org_unit_id');
    }
}
