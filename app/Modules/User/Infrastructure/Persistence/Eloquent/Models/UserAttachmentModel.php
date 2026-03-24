<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAttachmentModel extends Model
{
    use SoftDeletes;

    protected $table = 'user_attachments';
    protected $fillable = [
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
        'size'     => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
