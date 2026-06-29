<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class PaymentReversal extends TenantOwnedModel
{
    protected $table = 'payment_reversals';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'payment_id' => 'integer',
            'reversal_date' => 'date',
            'reversed_by' => 'integer',
            'original_amount' => 'decimal:6',
            'reversed_amount' => 'decimal:6',
        ]);
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Payment reversals are immutable.');
        });
        self::deleting(static function (): never {
            throw new LogicException('Payment reversals cannot be deleted.');
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }
}
