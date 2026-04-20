<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class TenantAttachmentModel extends BaseModel
{
    use HasAudit, SoftDeletes;
    use HasTenant;

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns this attachment.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    /**
     * Scope to filter by attachment type.
     */
    public function scopeByType(Builder $query, ?string $type): Builder
    {
        if ($type) {
            return $query->where('type', $type);
        }

        return $query;
    }
}
