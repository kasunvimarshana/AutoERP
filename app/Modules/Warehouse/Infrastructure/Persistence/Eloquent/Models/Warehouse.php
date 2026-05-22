<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Enums\WarehouseType;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class Warehouse extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'warehouses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => WarehouseType::class,
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(
            'Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Models\\WarehouseLocation',
            'warehouse_id'
        );
    }
}
