<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleServiceType extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'vehicle_service_types';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'depth' => 'integer',
            'standard_hours' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceType::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(VehicleServiceType::class, 'parent_id');
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceJobCard',
            'service_type_id'
        );
    }
}
