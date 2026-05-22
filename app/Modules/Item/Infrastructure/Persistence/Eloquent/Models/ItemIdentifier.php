<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Item\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ItemIdentifier extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'item_identifiers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'gs1_application_identifiers' => 'array',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\Batch',
            'batch_id'
        );
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\Serial',
            'serial_id'
        );
    }
}
