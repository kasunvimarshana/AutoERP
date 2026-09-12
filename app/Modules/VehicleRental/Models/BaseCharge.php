<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Constants\AgreementFields;

abstract class BaseCharge extends TenantOwnedModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), ['calculation' => 'array', 'amount' => 'decimal:'.AgreementFields::DECIMAL_SCALE, 'row_version' => 'integer']);
    }

    public function scopeForContext(Builder $query, int $tenant, int $organization): Builder
    {
        return $query->forTenant($tenant)->where('organization_unit_id', $organization);
    }

    protected static function booted(): void
    {
        static::updating(static function (self $charge): void {
            if ($charge->getRawOriginal('voided_at') !== null || $charge->voided_at === null
                || array_diff(array_keys($charge->getDirty()), ['voided_at', 'voided_by', 'void_reason', 'row_version', 'updated_at']) !== []) {
                throw new LogicException('Rental charge calculations are immutable; only a recorded void is permitted.');
            }
        });
        static::deleting(static fn () => throw new LogicException('Rental charge history must be preserved.'));
    }
}
