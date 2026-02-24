<?php
namespace Modules\Media\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class MediaModel extends Model
{
    use HasUuids, HasTenantScope, SoftDeletes;
    protected $table = 'media_files';
    protected $fillable = [
        'id', 'tenant_id', 'uploaded_by', 'disk', 'path', 'filename',
        'original_name', 'mime_type', 'size_bytes', 'folder', 'tags',
        'is_public', 'version', 'model_type', 'model_id',
    ];
    protected $casts = ['tags' => 'array', 'is_public' => 'boolean', 'size_bytes' => 'integer', 'version' => 'integer'];
}
