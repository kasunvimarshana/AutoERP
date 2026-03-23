<?php

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationUnitAttachmentModel extends Model
{
    use SoftDeletes;

    protected $table = 'organization_unit_attachments';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'size'     => 'integer',
    ];

    public function organizationUnit()
    {
        return $this->belongsTo(OrganizationUnitModel::class);
    }
}
