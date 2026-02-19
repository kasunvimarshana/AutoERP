<?php

declare(strict_types=1);

namespace Modules\Document\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Tenant\Traits\TenantScoped;

/**
 * DocumentTag Model
 *
 * Tags for categorizing and organizing documents
 */
class DocumentTag extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'color',
        'description',
    ];

    /**
     * Get documents with this tag
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_tag_relations');
    }
}
