<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\Vehicle\Models\Vehicle;

final class VehicleServiceLegacyHistory extends TenantOwnedModel
{
    public const UPDATED_AT = null;

    public const CREATED_AT = null;

    protected $table = 'vehicle_service_legacy_histories';

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new LogicException('Imported vehicle service history is immutable.'));
        self::deleting(static fn (): never => throw new LogicException('Imported vehicle service history cannot be deleted.'));
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'import_batch_id' => 'integer',
            'vehicle_id' => 'integer',
            'customer_id' => 'integer',
            'service_date' => 'date',
            'odometer_reading' => 'decimal:6',
            'next_service_mileage' => 'decimal:6',
            'source_payload' => 'array',
            'imported_at' => 'datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where(function (Builder $scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceLegacyImportBatch::class, 'import_batch_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(VehicleServiceLegacyHistoryItem::class, 'legacy_history_id')->orderBy('line_number');
    }
}
