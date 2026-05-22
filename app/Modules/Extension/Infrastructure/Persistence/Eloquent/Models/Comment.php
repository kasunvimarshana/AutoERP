<?php

declare(strict_types=1);

namespace Modules\Extension\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization as HasTenantAndOrganizationTrait;

class Comment extends Model
{
    use HasTenantAndOrganizationTrait;

    protected $table = 'comments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'commentable_type', 'commentable_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User',
            'author_id'
        );
    }
}
