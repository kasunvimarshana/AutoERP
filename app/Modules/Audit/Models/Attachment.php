<?php

namespace App\Modules\Audit\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends BaseModel
{
    protected $table = 'attachments';

    protected $fillable = [
        'tenant_id',
        'attachable_type',
        'attachable_id',
        'file_name',
        'file_path',
        'mime_type',
        'size',
        'label',
        'uploaded_by',
        'uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime'
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\User::class, 'uploaded_by');
    }
}
