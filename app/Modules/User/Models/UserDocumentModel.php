<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;

final class UserDocumentModel extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'user_documents';
    protected $fillable = [
        'tenant_id', 'row_version', 'user_id', 'name',
        'active_name_key', 'document_type', 'object_key', 'original_filename',
        'mime_type', 'size_bytes', 'checksum_sha256', 'scan_engine', 'scanned_at',
        'uploaded_by_user_id', 'updated_by_user_id',
    ];
    protected $hidden = ['object_key', 'active_name_key'];

    protected static function booted(): void
    {
        static::saving(function (self $document): void {
            $name = trim((string) $document->getAttribute('name'));
            $document->setAttribute('name', $name);
            $document->setAttribute('active_name_key', $document->getAttribute('deleted_at') === null ? mb_strtolower($name) : null);
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['row_version' => 'integer', 'size_bytes' => 'integer', 'scanned_at' => 'datetime']);
    }

    public function user(): BelongsTo { return $this->belongsTo(UserModel::class, 'user_id'); }
}
