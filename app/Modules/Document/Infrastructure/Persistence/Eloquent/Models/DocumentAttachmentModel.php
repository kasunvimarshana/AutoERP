<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAttachmentModel extends Model
{
    protected $table = 'document_attachments';

    protected $fillable = [
        'tenant_id',
        'document_id',
        'disk',
        'directory',
        'file_name',
        'stored_name',
        'mime_type',
        'file_size',
        'checksum',
        'uploaded_by',
    ];
}
