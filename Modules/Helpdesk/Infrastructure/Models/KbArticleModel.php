<?php

namespace Modules\Helpdesk\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class KbArticleModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'helpdesk_kb_articles';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'category_id',
        'author_id',
        'title',
        'body',
        'tags',
        'visibility',
        'status',
        'helpful_count',
        'not_helpful_count',
        'published_at',
        'archived_at',
    ];

    protected $casts = [
        'tags'         => 'array',
        'published_at' => 'datetime',
        'archived_at'  => 'datetime',
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
