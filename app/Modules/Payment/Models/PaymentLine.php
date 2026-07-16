<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class PaymentLine extends TenantOwnedModel
{
    protected $table = 'payment_lines';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'payment_id' => 'integer',
            'line_number' => 'integer',
            'payment_method_id' => 'integer',
            'requires_reference_snapshot' => 'boolean',
            'requires_instrument_details_snapshot' => 'boolean',
            'amount' => 'decimal:6',
            'cleared_amount' => 'decimal:6',
            'instrument_date' => 'date',
            'deposit_date' => 'date',
            'realized_date' => 'date',
            'clearing_date' => 'date',
            'bounced_date' => 'date',
            'returned_date' => 'date',
            'metadata' => 'array',
        ]);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
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
