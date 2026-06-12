<?php

declare(strict_types=1);

namespace Modules\Extension\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Extension\Enums\AttachmentPreviewStatus;
use Modules\Extension\Enums\AttachmentVisibility;
use Modules\User\Models\UserModel;

final class AttachmentModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'attachments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'attachable_id' => 'integer',
            'source_id' => 'integer',
            'source_context' => 'array',
            'size' => 'integer',
            'tags' => 'array',
            'version_number' => 'integer',
            'previous_version_id' => 'integer',
            'is_current' => 'boolean',
            'visibility' => AttachmentVisibility::class,
            'preview_status' => AttachmentPreviewStatus::class,
            'issued_at' => 'date',
            'expires_at' => 'date',
            'uploaded_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_by' => 'integer',
        ]);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'uploaded_by');
    }
}
