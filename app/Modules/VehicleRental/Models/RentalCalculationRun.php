<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalDocumentStatus;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalCalculationRun extends TenantOwnedModel
{
    use ScopesRentalContext;

    protected $table = 'rental_calculation_runs';
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::deleting(static function (RentalCalculationRun $run): void {
            $status = $run->calculation_status instanceof RentalCalculationStatus
                ? $run->calculation_status
                : RentalCalculationStatus::from((string) $run->calculation_status);

            if ($status !== RentalCalculationStatus::Draft) {
                throw new LogicException('Only draft rental calculation runs can be deleted. Use reversal for calculated, approved, or documented billing history.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'billing_period_id' => 'integer',
            'run_version' => 'integer',
            'supersedes_run_id' => 'integer',
            'currency_id' => 'integer',
            'calculation_status' => RentalCalculationStatus::class,
            'document_status' => RentalDocumentStatus::class,
            'net_total' => 'decimal:6',
            'discount_total' => 'decimal:6',
            'tax_total' => 'decimal:6',
            'withholding_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'metadata' => 'array',
            'calculated_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function billingPeriod(): BelongsTo { return $this->belongsTo(RentalBillingPeriod::class, 'billing_period_id'); }
    public function supersedesRun(): BelongsTo { return $this->belongsTo(self::class, 'supersedes_run_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(CurrencyModel::class, 'currency_id'); }
    public function lines(): HasMany { return $this->hasMany(RentalCalculationLine::class, 'calculation_run_id')->orderBy('line_number'); }
    public function sources(): HasMany { return $this->hasMany(RentalCalculationSource::class, 'calculation_run_id'); }
}
