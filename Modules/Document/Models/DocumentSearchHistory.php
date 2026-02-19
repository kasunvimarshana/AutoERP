<?php

declare(strict_types=1);

namespace Modules\Document\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Core\Traits\TenantScoped;
use Modules\Tenant\Models\Tenant;

/**
 * DocumentSearchHistory Model
 *
 * Stores user search history for documents
 */
class DocumentSearchHistory extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'document_search_history';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'query',
        'filters',
        'results_count',
    ];

    protected $casts = [
        'filters' => 'array',
        'results_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
