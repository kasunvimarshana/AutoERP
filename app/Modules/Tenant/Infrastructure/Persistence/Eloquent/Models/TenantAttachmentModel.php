<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantAttachmentModel extends Model
{
    use SoftDeletes;

    protected $table = 'tenant_attachments';

    protected $fillable = [
        'tenant_id',
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

    public function tenant()
    {
        return $this->belongsTo(TenantModel::class);
    }
}
