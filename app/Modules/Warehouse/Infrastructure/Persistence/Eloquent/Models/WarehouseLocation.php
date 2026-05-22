<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Enums\WarehouseLocationType;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class WarehouseLocation extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'warehouse_locations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'depth' => 'integer',
            'type' => WarehouseLocationType::class,
            'is_active' => 'boolean',
            'is_pickable' => 'boolean',
            'is_receivable' => 'boolean',
            'capacity' => 'decimal:4',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Models\\Warehouse',
            'warehouse_id'
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Models\\WarehouseLocation',
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            'Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Models\\WarehouseLocation',
            'parent_id'
        );
    }
}
