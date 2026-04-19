<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class UserAttachmentModel extends BaseModel
{

    use HasTenant;
    use HasAudit, SoftDeletes;

    protected $table = 'user_attachments';

    protected $fillable = [
        'tenant_id',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
