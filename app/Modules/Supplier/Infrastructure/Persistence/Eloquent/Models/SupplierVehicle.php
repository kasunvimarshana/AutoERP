<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class SupplierVehicle extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'supplier_vehicles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Vehicle\\Infrastructure\\Persistence\\Eloquent\\Models\\Vehicle',
            'vehicle_id'
        );
    }
}
