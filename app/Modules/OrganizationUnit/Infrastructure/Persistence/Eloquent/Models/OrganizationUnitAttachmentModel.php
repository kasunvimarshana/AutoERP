<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class OrganizationUnitAttachmentModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'organization_unit_attachments';

    protected $fillable = [
        'tenant_id',
        'organization_unit_id',
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

    public function organizationUnit()
    {
        return $this->belongsTo(OrganizationUnitModel::class);
    }
}
