<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\RunningChartStatus;

final class RunningChart extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_running_charts';

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['status' => RunningChartStatus::class, 'row_version' => 'integer', 'vehicle_use_version' => 'integer', 'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'finalized_at' => 'immutable_datetime', 'reversed_at' => 'immutable_datetime', 'start_odometer' => 'decimal:'.AgreementFields::DECIMAL_SCALE, 'end_odometer' => 'decimal:'.AgreementFields::DECIMAL_SCALE, 'garage_km' => 'decimal:'.AgreementFields::DECIMAL_SCALE, 'commercial_km' => 'decimal:'.AgreementFields::DECIMAL_SCALE, 'normal_ot_minutes' => 'integer', 'double_ot_minutes' => 'integer', 'triple_ot_minutes' => 'integer', 'night_outs' => 'integer']);
    }

    public function scopeForContext(Builder $q, int $tenant, int $organization): Builder
    {
        return $q->forTenant($tenant)->where('organization_unit_id', $organization);
    }

    public function vehicleUse(): BelongsTo
    {
        return $this->belongsTo(VehicleUse::class);
    }

    public function correctsChart(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_chart_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(RunningChartHistory::class)->orderBy('row_version');
    }

    public function totalKm(): ?string
    {
        return $this->start_odometer === null || $this->end_odometer === null ? null : bcsub($this->end_odometer, $this->start_odometer, AgreementFields::DECIMAL_SCALE);
    }

    protected static function booted(): void
    {
        self::deleting(static fn () => throw new LogicException('Preserve Running Chart evidence. Use reversal.'));
        self::updating(static function (self $record): void {
            if ($record->isDirty(['tenant_id', 'organization_unit_id', 'vehicle_use_id', 'corrects_chart_id'])) {
                throw new LogicException('Running Chart identity is immutable.');
            }
            if ($record->getRawOriginal('status') !== RunningChartStatus::Draft->value && array_diff(array_keys($record->getDirty()), ['status', 'reversed_at', 'row_version', 'updated_at']) !== []) {
                throw new LogicException('Finalized Running Chart facts are immutable.');
            }
        });
    }
}
