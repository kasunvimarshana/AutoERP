<?php

namespace Modules\DocumentManagement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class DocumentModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'documents';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'category_id',
        'title',
        'description',
        'file_path',
        'mime_type',
        'file_size',
        'status',
        'owner_id',
        'published_at',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
