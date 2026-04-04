<?php

namespace Modules\Attachment\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class AttachmentModel extends BaseModel
{
    protected $table = 'attachments';

    protected $fillable = [
        'tenant_id',
        'attachable_type',
        'attachable_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'label',
        'uploaded_by',
    ];
}
